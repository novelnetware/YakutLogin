jQuery(document).ready(function($) {
    'use strict';

    /**
     * ================================================================
     * Captcha Rendering Functions
     * ================================================================
     */
    function slrRenderReCaptcha() {
        document.querySelectorAll('.g-recaptcha').forEach(function(el) {
            if (el.innerHTML.trim() === '') {
                grecaptcha.render(el, {
                    'sitekey': el.getAttribute('data-sitekey')
                });
            }
        });
    }

    function slrRenderTurnstile() {
        document.querySelectorAll('.cf-turnstile').forEach(function(el) {
            if (el.innerHTML.trim() === '') {
                turnstile.render(el, {
                    'sitekey': el.getAttribute('data-sitekey')
                });
            }
        });
    }

    window.slrRenderReCaptcha = slrRenderReCaptcha;
    window.slrRenderTurnstile = slrRenderTurnstile;
    /**
     * ================================================================
     * Main Form Initializer
     * ================================================================
     */
    function initializeForm(formContainer) {
        const $container = $(formContainer);
        const $form = $container.find('.slr-otp-form');
        if ($form.length === 0) return;

        let identifierValue = '';
        let timerInterval; 

        // ذخیره کردن المان‌ها در متغیرها
    const $sendOtpBtn = $form.find('.slr-send-otp-button');
    const $submitBtn = $form.find('.slr-submit-button');
    const $identifierField = $form.find('.slr-identifier-input');
    const $messageArea = $form.find('.slr-message-area');
    const $otpInput = $form.find('.slr-otp-input');
    
    // بخش‌های مختلف فرم
    const $identifierRow = $form.find('.slr-identifier-row');
    const $otpRow = $form.find('.slr-otp-row');
    const $submitRow = $form.find('.slr-submit-row');
    const $captchaRow = $form.find('.slr-captcha-row');
    const $orDivider = $form.find('.slr-or-divider');
    const $socialRow = $form.find('.slr-social-login-row');
    
    // المان‌های جدید
    const $timerDisplay = $form.find('.slr-timer');
    const $countdownSpan = $form.find('.slr-countdown');
    const $resendBtn = $form.find('.slr-resend-otp-button');
    const $backBtn = $form.find('.slr-back-button');

        // تابع نمایش پیام
    function displayMessage(message, type = 'error') {
        $messageArea.text(message).removeClass('slr-success slr-error slr-info').addClass(`slr-${type}`).slideDown();
    }

    // تابع ریست کردن کامل فرم
    function resetForm() {
        // نمایش بخش‌های اولیه
        $identifierRow.slideDown();
        $captchaRow.slideDown();
        $orDivider.slideDown();
        $socialRow.slideDown();
        
        // پنهان کردن بخش‌های OTP
        $otpRow.slideUp();
        $submitRow.slideUp();
        
        // فعال‌سازی و ریست کردن فیلدها و دکمه‌ها
        $identifierField.val('').prop('readonly', false).focus();
        $otpInput.val('');
        $sendOtpBtn.prop('disabled', false);
        displayMessage('', 'info'); // پاک کردن پیام
        
        // ریست کردن کپچا
        if (typeof grecaptcha !== 'undefined' && grecaptcha.reset) grecaptcha.reset();
        if (typeof turnstile !== 'undefined' && turnstile.reset) turnstile.reset();

        // متوقف کردن و پنهان کردن تایمر
        clearInterval(timerInterval);
        $timerDisplay.hide();
        $resendBtn.hide();
    }

    // تابع شروع تایمر
    function startTimer(duration = 60) {
        let timer = duration;
        $countdownSpan.text(timer);
        $timerDisplay.show();
        $resendBtn.hide();
        $sendOtpBtn.prop('disabled', true); // غیرفعال کردن دکمه ارسال اصلی

        timerInterval = setInterval(function() {
            timer--;
            $countdownSpan.text(timer);
            if (timer <= 0) {
                clearInterval(timerInterval);
                $timerDisplay.hide();
                $resendBtn.show().prop('disabled', false); // نمایش دکمه ارسال مجدد
            }
        }, 1000);
    }
    
    // رویداد کلیک دکمه بازگشت
    $backBtn.on('click', function(e) {
        e.preventDefault();
        resetForm();
    });

    // رویداد کلیک برای ارسال مجدد (که همان تابع ارسال کد را فراخوانی می‌کند)
    $resendBtn.on('click', function(e){
        e.preventDefault();
        $(this).prop('disabled', true);
        $sendOtpBtn.trigger('click');
    });



        // رویداد کلیک دکمه اصلی "ارسال کد تایید"
    $sendOtpBtn.on('click', async function(e) {
        e.preventDefault();
        identifierValue = $identifierField.val();
        if (!identifierValue) {
            displayMessage('لطفا ایمیل یا شماره تلفن خود را وارد کنید.', 'error');
            return;
        }

        const originalButtonText = $(this).text(); // فقط متن را می‌گیریم
        $(this).prop('disabled', true).html(slr_public_data.text_sending_otp);
        displayMessage('در حال ارسال کد...', 'info');

        const formData = new URLSearchParams();
        formData.append('action', 'slr_send_otp');
        formData.append('security', slr_public_data.send_otp_nonce);
        formData.append('identifier', identifierValue);

        // **رفع مشکل کپچا**: ارسال توکن برای هر دو سرویس
        const recaptchaToken = $form.find('[name="g-recaptcha-response"]').val();
        if (recaptchaToken) formData.append('g-recaptcha-response', recaptchaToken);
        
        const turnstileToken = $form.find('[name="cf-turnstile-response"]').val();
        if (turnstileToken) formData.append('cf-turnstile-response', turnstileToken);

        try {
            const response = await fetch(slr_public_data.ajax_url, { method: 'POST', body: formData });
            const result = await response.json();
            if (result.success) {
                displayMessage(result.data.message, 'success');
                // **قابلیت جدید**: پنهان کردن المان‌های اضافی
                $identifierRow.slideUp();
                $captchaRow.slideUp();
                $orDivider.slideUp();
                $socialRow.slideUp();

                // نمایش بخش OTP و فوکوس روی فیلد
                $otpRow.slideDown().find('input').focus();
                $submitRow.slideDown();
                $identifierField.prop('readonly', true);
                
                // شروع تایمر
                startTimer(60); 
            } else {
                throw new Error(result.data.message || 'خطای ناشناخته رخ داد.');
            }
        } catch (error) {
            displayMessage(error.message, 'error');
            $(this).prop('disabled', false).html(originalButtonText);
            resetForm(); // ریست کردن فرم در صورت بروز خطا
        }
    });

        // --- OTP Form Submit Handler ---
        $form.on('submit', async function(e) {
            e.preventDefault();
            const originalButtonText = $submitBtn.html();
            $submitBtn.prop('disabled', true).html(slr_public_data.text_processing);
            displayMessage('در حال پردازش...', 'info');

            const formData = new URLSearchParams(new FormData(this));
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
    /**
     * ================================================================
     * Execution
     * ================================================================
     */
    $('.slr-otp-form-container').each(function() {
        initializeForm(this);
    });
});