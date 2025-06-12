jQuery(document).ready(function($) {
    'use strict';

    // نمایش دکمه ورود بیومتریک در صورت پشتیبانی مرورگر
    const webAuthnButton = $('#slr-webauthn-login-button');
    if (window.PublicKeyCredential && webAuthnButton.length) {
        webAuthnButton.show();
    }

    // Event listener برای دکمه ورود بیومتریک
    $('body').on('click', '#slr-webauthn-login-button', async function(e) {
        e.preventDefault();
        const $button = $(this);
        const $formContainer = $button.closest('.slr-otp-form-container');
        const $identifierField = $formContainer.find('.slr-identifier-input');
        const $messageDiv = $formContainer.find('.slr-message-area');
        const identifier = $identifierField.val();

        if (!identifier) {
            $messageDiv.text('برای ورود با اثر انگشت، ابتدا ایمیل یا شماره تلفن خود را وارد کنید.').removeClass('slr-success').addClass('slr-error');
            return;
        }

        $button.html('<i class="fas fa-spinner fa-spin"></i> در حال پردازش...').prop('disabled', true);
        $messageDiv.text('لطفا هویت خود را با دستگاه تایید کنید...').removeClass('slr-error slr-success').addClass('slr-info');

        try {
            // ۱. دریافت گزینه‌های ورود از سرور
            const requestOptionsResponse = await $.post(slr_public_data.ajax_url, {
                action: 'yakutlogin_get_authentication_options',
                nonce: slr_public_data.send_otp_nonce, // استفاده از نانس موجود
                identifier: identifier
            });

            if (!requestOptionsResponse.success) throw new Error(requestOptionsResponse.data.message);

            // ۲. آماده‌سازی گزینه‌ها و فراخوانی API مرورگر
            const credentialOptions = prepareOptionsForBrowser(requestOptionsResponse.data);
            const assertion = await navigator.credentials.get({ publicKey: credentialOptions });

            // ۳. ارسال نتیجه به سرور برای تایید نهایی
            const verificationResponse = await fetch(slr_public_data.ajax_url, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    ...prepareCredentialForServer(assertion),
                    action: 'yakutlogin_verify_authentication',
                    nonce: slr_public_data.send_otp_nonce,
                    identifier: identifier
                })
            });
            const verificationResult = await verificationResponse.json();
            
            if (verificationResult.success && verificationResult.data.redirect_url) {
                $messageDiv.text(verificationResult.data.message).removeClass('slr-error').addClass('slr-success');
                window.location.href = verificationResult.data.redirect_url;
            } else {
                throw new Error(verificationResult.data.message);
            }

        } catch (err) {
            console.error("WebAuthn Login Error:", err);
            $messageDiv.text(err.message || 'ورود با خطا مواجه شد.').removeClass('slr-success').addClass('slr-error');
        } finally {
            $button.html('<i class="fas fa-fingerprint"></i> ورود با اثر انگشت').prop('disabled', false);
        }
    });

    // --- WebAuthn Helper Functions ---

function bufferDecode(value) {
    return Uint8Array.from(atob(value.replace(/_/g, '/').replace(/-/g, '+')), c => c.charCodeAt(0));
}

function bufferEncode(value) {
    return btoa(String.fromCharCode.apply(null, new Uint8Array(value)))
        .replace(/\+/g, '-').replace(/\//g, '_').replace(/=/g, '');
}

function prepareOptionsForBrowser(options) {
    options.challenge = bufferDecode(options.challenge);
    options.user.id = bufferDecode(options.user.id);
    if (options.excludeCredentials) {
        options.excludeCredentials.forEach(cred => {
            cred.id = bufferDecode(cred.id);
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
            attestationObject: bufferEncode(credential.response.attestationObject),
            clientDataJSON: bufferEncode(credential.response.clientDataJSON),
        },
    };
}


    // Helper function to validate email
    function isValidEmail(email) {
        var emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    }

    // --- Send OTP Handler ---
    // For generic OTP forms (shortcode, widget) and wp-login.php forms
    // Uses data-attributes on the button if available, otherwise specific IDs.
    // --- Send OTP Handler ---
    $('body').on('click', '.slr-send-otp-button', function(e) {
        e.preventDefault();
        var $button = $(this);
        var $formContainer = $button.closest('.slr-otp-form-container');
        
        // Find the new unified identifier field
        var $identifierField = $formContainer.find('.slr-identifier-input').first();
        var identifier = $identifierField.val();

        var $messageDiv = $formContainer.find('.slr-message-area').first();
        
        $messageDiv.text(slr_public_data.text_sending_otp).removeClass('slr-error slr-success').addClass('slr-info');

        if (!identifier) {
            $messageDiv.text('لطفا ایمیل یا شماره تلفن خود را وارد کنید.').addClass('slr-error');
            return;
        }

        $button.prop('disabled', true);

        $.ajax({
            url: slr_public_data.ajax_url,
            type: 'POST',
            data: {
                action: 'slr_send_otp',
                identifier: identifier, // Send the single identifier
                security: slr_public_data.send_otp_nonce
                // Include captcha response if present
            },
            success: function(response) {
                if (response.success) {
                    $messageDiv.text(response.data.message).addClass('slr-success');
                    $formContainer.find('.slr-otp-row, .slr-submit-row').slideDown();
                } else {
                    $messageDiv.text(response.data.message).addClass('slr-error');
                }
            },
            error: function() {
                $messageDiv.text(slr_public_data.text_error_sending_otp).addClass('slr-error');
            },
            complete: function() {
                setTimeout(function() { $button.prop('disabled', false); }, 2000);
            }
        });
    });

    // --- Process Login/Register Form Submission Handler ---
    $('body').on('submit', 'form.slr-otp-form', function(e) {
        e.preventDefault();
        var $form = $(this);
        var $messageDiv = $form.find('.slr-message-area');
        var $submitButton = $form.find('.slr-submit-button');

        var identifier = $form.find('.slr-identifier-input').val(); // Get identifier from the form
        var otp = $form.find('.slr-otp-input').val();
        var processNonce = $form.find('input[name="slr_process_form_nonce_field"]').val();
        var redirectTo = $form.find('input[name="slr_redirect_to"]').val() || '';


        $messageDiv.text(slr_public_data.text_processing).removeClass('slr-error slr-success').addClass('slr-info');
        $submitButton.prop('disabled', true);

        if (!otp) {
            $messageDiv.text(slr_public_data.text_fill_otp).addClass('slr-error');
            $submitButton.prop('disabled', false);
            return;
        }

        $.ajax({
            url: slr_public_data.ajax_url,
            type: 'POST',
            data: {
                action: 'slr_process_login_register_otp',
                identifier: identifier,
                otp_code: otp,
                slr_process_form_nonce_field: processNonce, // Nonce for this specific action
                redirect_to: redirectTo,
                // context: $form.find('input[name="slr_form_context"]').val() // if needed by backend
            },
            success: function(response) {
                if (response.success) {
                    $messageDiv.text(response.data.message).addClass('slr-success');
                    if (response.data.redirect_url) {
                        window.location.href = response.data.redirect_url;
                    } else {
                         // Fallback if no redirect URL, maybe refresh or show specific message.
                        // For now, just disable button on success to prevent resubmit.
                    }
                } else {
                    $messageDiv.text(response.data.message || 'An error occurred.').addClass('slr-error');
                    $submitButton.prop('disabled', false);
                }
            },
            error: function() {
                $messageDiv.text('A critical error occurred. Please try again.').addClass('slr-error');
                $submitButton.prop('disabled', false);
            },
            // complete: function() { // Button re-enabled on error explicitly above }
        });
    });
    
    // Basic styling (add to your public CSS file)
    var styles = `
        .slr-message-area { margin-top: 10px; padding: 8px; border-radius: 3px; }
        .slr-message-area.slr-error { color: #721c24; background-color: #f8d7da; border: 1px solid #f5c6cb; }
        .slr-message-area.slr-success { color: #155724; background-color: #d4edda; border: 1px solid #c3e6cb; }
        .slr-message-area.slr-info { color: #0c5460; background-color: #d1ecf1; border: 1px solid #bee5eb; }
        .slr-otp-row, .slr-submit-row { margin-top: 10px; }
    `;
    if (!$('style#slr-dynamic-styles').length) {
        $('head').append('<style id="slr-dynamic-styles">' + styles + '</style>');
    }
});