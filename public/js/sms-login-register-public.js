jQuery(document).ready(function($) {
    'use strict';

    /**
     * ----------------------------------------------------------------
     * WebAuthn Helper Functions
     * (These are correct and do not need changes)
     * ----------------------------------------------------------------
     */
    function bufferDecode(value) {
        return Uint8Array.from(atob(value.replace(/_/g, '/').replace(/-/g, '+')), c => c.charCodeAt(0));
    }

    function bufferEncode(value) {
        return btoa(String.fromCharCode.apply(null, new Uint8Array(value)))
            .replace(/\+/g, '-').replace(/\//g, '_').replace(/=/g, '');
    }

    function prepareOptionsForBrowser(options) {
        if (options.challenge) options.challenge = bufferDecode(options.challenge);
        if (options.user && options.user.id) options.user.id = bufferDecode(options.user.id);
        if (options.allowCredentials) {
            options.allowCredentials.forEach(cred => {
                if (cred.id) cred.id = bufferDecode(cred.id);
            });
        }
        return options;
    }

    function prepareCredentialForServer(credential) {
        return {
            id: credential.id,
            rawId: bufferEncode(credential.rawId),
            type: credential.type,
            response: {
                authenticatorData: bufferEncode(credential.response.authenticatorData),
                clientDataJSON: bufferEncode(credential.response.clientDataJSON),
                signature: bufferEncode(credential.response.signature),
                userHandle: credential.response.userHandle ? bufferEncode(credential.response.userHandle) : null,
            },
        };
    }

    /**
     * ----------------------------------------------------------------
     * Main Form Initializer
     * Attaches all event listeners to a specific form instance.
     * ----------------------------------------------------------------
     */
    function initializeForm(formContainer) {
        const $container = $(formContainer);
        const $form = $container.find('.slr-otp-form');
        if ($form.length === 0) return;

        // --- بخش اصلاح شده ---
        let identifierValue = ''; // متغیری برای ذخیره مقدار فیلد اصلی

        // Find all elements within this specific form instance
        const $sendOtpBtn = $form.find('.slr-send-otp-button');
        const $submitBtn = $form.find('.slr-submit-button');
        const $webAuthnBtn = $form.find('.slr-webauthn-login-button');
        const $identifierField = $form.find('.slr-identifier-input');
        const $messageArea = $form.find('.slr-message-area');

        // Form sections
        const $sendOtpRow = $form.find('.slr-send-otp-row');
        const $otpRow = $form.find('.slr-otp-row');
        const $submitRow = $form.find('.slr-submit-row');
        const $captchaRow = $form.find('.slr-captcha-row');
        
        // Helper to display messages
        function displayMessage(message, type = 'error') {
            $messageArea.text(message).removeClass('slr-success slr-error slr-info').addClass(`slr-${type}`).slideDown();
        }

        // 1. "Send Verification Code" Button Click Handler
        $sendOtpBtn.on('click', async function(e) {
            e.preventDefault();

            identifierValue = $identifierField.val(); // مقدار را در متغیر ذخیره می‌کنیم
            if (!identifierValue) {
                displayMessage('لطفا ایمیل یا شماره تلفن خود را وارد کنید.', 'error');
                return;
            }

            const originalButtonText = $(this).html();
            $(this).prop('disabled', true).html(slr_public_data.text_sending_otp);
            displayMessage('در حال ارسال کد...', 'info');

            const formData = new URLSearchParams();
            formData.append('action', 'slr_send_otp');
            formData.append('security', slr_public_data.send_otp_nonce);
            formData.append('identifier', $identifierField.val());
            
            const recaptchaToken = $form.find('[name="g-recaptcha-response"]').val();
            if (recaptchaToken) formData.append('g-recaptcha-response', recaptchaToken);

            try {
                const response = await fetch(slr_public_data.ajax_url, { method: 'POST', body: formData });
                const result = await response.json();

                if (result.success) {
                    displayMessage(result.data.message, 'success');
                    $sendOtpRow.slideUp();
                    $captchaRow.slideUp();
                    $otpRow.slideDown();
                    $submitRow.slideDown();
                    $identifierField.prop('readonly', true);
                } else {
                    throw new Error(result.data.message || 'خطای ناشناخته رخ داد.');
                }
            } catch (error) {
                displayMessage(error.message, 'error');
                $(this).prop('disabled', false).html(originalButtonText);
                if (typeof grecaptcha !== 'undefined') grecaptcha.reset();
            }
        });

        // 2. Main Form Submission (Login/Register with OTP)
        $form.on('submit', async function(e) {
            e.preventDefault();
            const originalButtonText = $submitBtn.html();
            $submitBtn.prop('disabled', true).html(slr_public_data.text_processing);
            displayMessage('در حال پردازش...', 'info');

            const formData = new URLSearchParams(new FormData(this));

            // --- بخش اصلاح شده ---
            // مقدار identifier را از متغیری که قبلا ذخیره کرده‌ایم می‌خوانیم
            formData.set('slr_identifier', identifierValue); 

            formData.append('action', 'slr_process_login_register_otp');

            try {
                const response = await fetch(slr_public_data.ajax_url, { method: 'POST', body: formData });
                const result = await response.json();

                if (result.success) {
                    displayMessage(result.data.message, 'success');
                    if (result.data.redirect_url) {
                        window.location.href = result.data.redirect_url;
                    }
                } else {
                    throw new Error(result.data.message || 'خطای ناشناخته رخ داد.');
                }
            } catch (error) {
                displayMessage(error.message, 'error');
                $submitBtn.prop('disabled', false).html(originalButtonText);
            }
        });
        
        // 3. WebAuthn Login Button Click Handler
        if (window.PublicKeyCredential && $webAuthnBtn.length) {
            $webAuthnBtn.show(); // Show if supported
            $webAuthnBtn.on('click', async function(e) {
                e.preventDefault();
                const identifier = $identifierField.val();
                if (!identifier) {
                    displayMessage('برای ورود با اثر انگشت، ابتدا ایمیل یا شماره تلفن خود را وارد کنید.', 'error');
                    return;
                }

                const originalButtonHTML = $(this).html();
                $(this).prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');
                displayMessage('لطفا هویت خود را با دستگاه تایید کنید...', 'info');
                
                try {
                    // Get options from server
                    const optionsResponse = await fetch(slr_public_data.ajax_url, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: `action=yakutlogin_get_authentication_options&identifier=${identifier}&nonce=${slr_public_data.send_otp_nonce}`
                    });
                    const optionsJSON = await optionsResponse.json();
                    if (!optionsJSON.success) throw new Error(optionsJSON.data.message);

                    // Call browser API
                    const credentialOptions = prepareOptionsForBrowser(optionsJSON.data);
                    const assertion = await navigator.credentials.get({ publicKey: credentialOptions });
                    
                    // Verify with server
                    const verificationResponse = await fetch(slr_public_data.ajax_url, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            action: 'yakutlogin_verify_authentication',
                            nonce: slr_public_data.send_otp_nonce,
                            identifier: identifier,
                            ...prepareCredentialForServer(assertion)
                        })
                    });
                    const verificationResult = await verificationResponse.json();

                    if (verificationResult.success) {
                        displayMessage(verificationResult.data.message, 'success');
                        if (verificationResult.data.redirect_url) {
                            window.location.href = verificationResult.data.redirect_url;
                        }
                    } else {
                        throw new Error(verificationResult.data.message);
                    }
                } catch (err) {
                    displayMessage(err.message || 'ورود با خطا مواجه شد.', 'error');
                    $(this).prop('disabled', false).html(originalButtonHTML);
                }
            });
        }
    }

    /**
     * ----------------------------------------------------------------
     * Execution
     * Find all forms on the page and initialize them.
     * ----------------------------------------------------------------
     */
    $('.slr-otp-form-container').each(function() {
        initializeForm(this);
    });

});