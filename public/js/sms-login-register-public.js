jQuery(document).ready(function($) {
    'use strict';

    /**
     * ================================================================
     * 1. SHARED HELPER FUNCTIONS
     * ================================================================
     */

    // A single place to hold the OTP timer interval ID to prevent conflicts.
    let otpTimerInterval;

    /**
     * Displays a message in the form's message area.
     * @param {jQuery} $formContainer The .slr-otp-form-container element.
     * @param {string} message The message to display.
     * @param {string} type The message type ('success', 'error', 'info').
     */
    function displayMessage($formContainer, message, type = 'error') {
        const $messageArea = $formContainer.find('.slr-message-area');
        $messageArea.text(message).removeClass('slr-success slr-error slr-info').addClass(`slr-${type}`).slideDown();
    }
    
    /**
     * Starts the OTP countdown timer for a specific form.
     * @param {jQuery} $formContainer The .slr-otp-form-container element.
     * @param {number} duration The countdown duration in seconds.
     */
    const startOtpTimer = ($formContainer, duration = 60) => {
        clearInterval(otpTimerInterval);
        let timer = duration;
        const $timerDisplay = $formContainer.find('.slr-timer');
        const $countdownSpan = $formContainer.find('.slr-countdown');
        const $resendBtn = $formContainer.find('.slr-resend-otp-button');
        
        $timerDisplay.show();
        $resendBtn.hide();

        otpTimerInterval = setInterval(() => {
            timer--;
            $countdownSpan.text(timer);
            if (timer <= 0) {
                clearInterval(otpTimerInterval);
                $timerDisplay.hide();
                $resendBtn.show();
            }
        }, 1000);
    };

    /**
     * ================================================================
     * 2. CAPTCHA RENDERING
     * ================================================================
     */
    function slrRenderReCaptcha() {
        document.querySelectorAll('.g-recaptcha').forEach(function(el) {
            if (el.innerHTML.trim() === '' && typeof grecaptcha !== 'undefined') {
                grecaptcha.render(el, { 'sitekey': el.getAttribute('data-sitekey') });
            }
        });
    }

    function slrRenderTurnstile() {
        document.querySelectorAll('.cf-turnstile').forEach(function(el) {
            if (el.innerHTML.trim() === '' && typeof turnstile !== 'undefined') {
                turnstile.render(el, { 'sitekey': el.getAttribute('data-sitekey') });
            }
        });
    }
    window.slrRenderReCaptcha = slrRenderReCaptcha;
    window.slrRenderTurnstile = slrRenderTurnstile;

    /**
     * ================================================================
     * 3. MAIN FORM LOGIC INITIALIZER
     * ================================================================
     */
    function initializeForm(formContainer) {
        const $container = $(formContainer);
        const $form = $container.find('.slr-otp-form');
        if ($form.data('initialized')) return;

        const elements = {
            sendOtpBtn: $form.find('.slr-send-otp-button'),
            submitBtn: $form.find('.slr-submit-button'),
            identifierField: $form.find('.slr-identifier-input'),
            identifierRow: $form.find('.slr-identifier-row'),
            sendOtpRow: $form.find('.slr-send-otp-row'),
            otpRow: $form.find('.slr-otp-row'),
            submitRow: $form.find('.slr-submit-row'),
            captchaRow: $form.find('.slr-captcha-row'),
            orDivider: $form.find('.slr-or-divider'),
            socialIconsWrapper: $form.find('.slr-social-icons-wrapper'),
            backBtn: $form.find('.slr-back-button'),
            resendBtn: $form.find('.slr-resend-otp-button')
        };

        // Handler for the main "Send OTP" button (for Email/SMS)
        elements.sendOtpBtn.on('click', async function(e) {
            e.preventDefault();
            const identifier = elements.identifierField.val();
            if (!identifier) {
                displayMessage($container, 'لطفا ایمیل یا شماره تلفن خود را وارد کنید.', 'error');
                return;
            }
            const originalHtml = $(this).html();
            $(this).prop('disabled', true).text(slr_public_data.text_sending_otp);
            const formData = new FormData($form[0]);
            formData.append('action', 'slr_send_otp');
            formData.append('security', slr_public_data.send_otp_nonce);

            try {
                const response = await fetch(slr_public_data.ajax_url, { method: 'POST', body: formData });
                const result = await response.json();
                if (result.success) {
                    displayMessage($container, result.data.message, 'success');
                    elements.identifierRow.hide();
                    elements.sendOtpRow.hide();
                    elements.captchaRow.hide();
                    elements.orDivider.hide();
                    elements.socialIconsWrapper.hide();
                    elements.otpRow.slideDown();
                    elements.submitRow.slideDown();
                    elements.identifierField.prop('readonly', true);
                    setTimeout(() => elements.otpRow.find('.slr-otp-digit').first().focus(), 100);
                    startOtpTimer($container, 60);
                } else {
                    throw new Error(result.data.message || 'خطای ناشناخته');
                }
            } catch (error) {
                displayMessage($container, error.message, 'error');
            } finally {
                $(this).prop('disabled', false).html(originalHtml);
            }
        });

        // Handler for the final form submission with OTP
        $form.on('submit', async function(e) {
            e.preventDefault();
            const originalHtml = elements.submitBtn.html();
            elements.submitBtn.prop('disabled', true).html(slr_public_data.text_processing);
            const formData = new FormData(this);
            formData.append('action', 'slr_process_login_register_otp');
            try {
                const response = await fetch(slr_public_data.ajax_url, { method: 'POST', body: formData });
                const result = await response.json();
                if (result.success) {
                    displayMessage($container, 'ورود موفقیت آمیز بود. در حال انتقال...', 'success');
                    window.location.href = result.data.redirect_url;
                } else {
                    throw new Error(result.data.message);
                }
            } catch (error) {
                displayMessage($container, error.message, 'error');
                elements.submitBtn.prop('disabled', false).html(originalHtml);
            }
        });
        
        // Handler for "Back" and "Resend" links
        elements.backBtn.on('click', (e) => {
            e.preventDefault();
            elements.identifierRow.slideDown();
            elements.sendOtpRow.show();
            elements.captchaRow.slideDown();
            elements.orDivider.slideDown();
            elements.socialIconsWrapper.show();
            elements.otpRow.slideUp();
            elements.submitRow.slideUp();
            elements.identifierField.prop('readonly', false).focus();
            clearInterval(otpTimerInterval);
        });
        elements.resendBtn.on('click', (e) => {
            e.preventDefault();
            elements.sendOtpBtn.trigger('click');
        });

        $form.data('initialized', true);
    }

    /**
     * ================================================================
     * 4. SOCIAL LOGIN & BALE OTP LOGIC
     * ================================================================
     */
    function initializeSocialLogins() {
        // Helper function to send OTP via Bale when the Bale icon is clicked
        const sendBaleOtp = async ($formContainer) => {
            const identifier = $formContainer.find('.slr-identifier-input').val();
            if (!identifier) {
                displayMessage($formContainer, 'لطفاً ابتدا شماره تلفن خود را در کادر اصلی وارد کنید.', 'error');
                return;
            }
            
            const $baleIcon = $formContainer.find('.icon.bale');
            $baleIcon.css('pointer-events', 'none').animate({ opacity: 0.5 });
            displayMessage($formContainer, 'در حال ارسال کد به بله...', 'info');

            const formData = new FormData();
            formData.append('action', 'slr_send_bale_otp');
            formData.append('security', slr_public_data.bale_otp_nonce);
            formData.append('phone_number', identifier);

            try {
                const response = await fetch(slr_public_data.ajax_url, { method: 'POST', body: formData });
                const result = await response.json();
                if (result.success) {
                    displayMessage($formContainer, result.data.message, 'success');
                    $formContainer.find('.slr-identifier-row, .slr-send-otp-row, .slr-social-icons-wrapper, .slr-or-divider, .slr-captcha-row').hide();
                    $formContainer.find('.slr-otp-row, .slr-submit-row').slideDown();
                    $formContainer.find('.slr-identifier-input').prop('readonly', true);
                    setTimeout(() => $formContainer.find('.slr-otp-digit').first().focus(), 100);
                    startOtpTimer($formContainer, 60);
                } else {
                    throw new Error(result.data.message);
                }
            } catch (error) {
                displayMessage($formContainer, error.message, 'error');
            } finally {
                $baleIcon.css('pointer-events', 'auto').animate({ opacity: 1 });
            }
        };

        // UNIFIED CLICK HANDLER for all social icons
        $('body').on('click', '.slr-social-icons-wrapper .icon', function(e) {
            e.preventDefault();
            const $icon = $(this);
            const provider = $icon.data('provider');
            const url = $icon.data('url');
            const $formContainer = $icon.closest('.slr-otp-form-container');

            switch (provider) {
                case 'bale':
                    // Per our final decision, the Bale button only sends an OTP.
                    sendBaleOtp($formContainer);
                    break;
                case 'google':
                case 'github':
                case 'linkedin':
                case 'discord':
                    // For all other social logins that have a direct URL
                    if (url && url !== '#') {
                        window.location.href = url;
                    }
                    break;
            }
        });
    }

    /**
     * ================================================================
     * 5. Professional OTP Inputs Handler
     * ================================================================
     */
    function initializeOtpInputs() {
        $('body').on('input paste', '.slr-otp-digit', function(e) {
            const $input = $(this);
            const $form = $input.closest('form');
            const $inputs = $form.find('.slr-otp-digit');
            
            if (e.type === 'paste') {
                e.preventDefault();
                const pasteData = (e.originalEvent.clipboardData || window.clipboardData).getData('text');
                const digits = pasteData.replace(/\D/g, '').split('');
                digits.forEach((digit, index) => {
                    if ($inputs.eq(index).length) $inputs.eq(index).val(digit);
                });
            }
            
            const finalOtp = Array.from($inputs).map(i => i.value).join('');
            $form.find('.slr-otp-input-hidden').val(finalOtp);
            
            if ($input.val() && $input.data('index') < $inputs.length - 1) {
                $inputs.eq($input.data('index') + 1).focus();
            }
            if (finalOtp.length === $inputs.length) {
                $form.find('.slr-submit-button').focus();
            }
        });

        $('body').on('keydown', '.slr-otp-digit', function(e) {
            if (e.key === 'Backspace' && !this.value) {
                const index = $(this).data('index');
                if (index > 0) $(this).closest('.slr-otp-inputs').find('.slr-otp-digit').eq(index - 1).focus();
            }
        });
    }

    /**
     * ================================================================
     * 6. EXECUTION ON PAGE LOAD
     * ================================================================
     */
    $('.slr-otp-form-container').each(function() {
        initializeForm(this);
    });
    
    initializeOtpInputs();
    initializeSocialLogins(); // This single function now handles all social icons.

    // Initial render of any CAPTCHAs that might be on the page
    slrRenderReCaptcha();
    slrRenderTurnstile();
});