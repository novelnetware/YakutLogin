jQuery(document).ready(function($) {
    'use strict';

    /**
     * ================================================================
     * Captcha Rendering Functions
     * ================================================================
     */
    function slrRenderReCaptcha() {
        document.querySelectorAll('.g-recaptcha').forEach(function(el) {
            if (el.innerHTML.trim() === '' && typeof grecaptcha !== 'undefined') {
                grecaptcha.render(el, {
                    'sitekey': el.getAttribute('data-sitekey')
                });
            }
        });
    }

    function slrRenderTurnstile() {
        document.querySelectorAll('.cf-turnstile').forEach(function(el) {
            if (el.innerHTML.trim() === '' && typeof turnstile !== 'undefined') {
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
     * Main OTP Form Initializer
     * ================================================================
     */
    function initializeForm(formContainer) {
        const $container = $(formContainer);
        const $form = $container.find('.slr-otp-form');
        if ($form.length === 0) return;

        let identifierValue = '';
        let timerInterval;

        const $sendOtpBtn = $form.find('.slr-send-otp-button');
        const $submitBtn = $form.find('.slr-submit-button');
        const $identifierField = $form.find('.slr-identifier-input');
        const $messageArea = $form.find('.slr-message-area');
        const $otpInput = $form.find('.slr-otp-input');

        const $identifierRow = $form.find('.slr-identifier-row');
        const $otpRow = $form.find('.slr-otp-row');
        const $submitRow = $form.find('.slr-submit-row');
        const $captchaRow = $form.find('.slr-captcha-row');
        const $orDivider = $form.find('.slr-or-divider');
        const $socialRow = $form.find('.slr-social-login-row');

        const $timerDisplay = $form.find('.slr-timer');
        const $countdownSpan = $form.find('.slr-countdown');
        const $resendBtn = $form.find('.slr-resend-otp-button');
        const $backBtn = $form.find('.slr-back-button');

        function displayMessage(message, type = 'error') {
            $messageArea.text(message).removeClass('slr-success slr-error slr-info').addClass(`slr-${type}`).slideDown();
        }

        function resetForm() {
            $identifierRow.slideDown();
            $captchaRow.slideDown();
            $orDivider.slideDown();
            $socialRow.slideDown();

            $otpRow.slideUp();
            $submitRow.slideUp();

            $identifierField.val('').prop('readonly', false).focus();
            $otpInput.val('');
            $sendOtpBtn.prop('disabled', false);
            displayMessage('', 'info');

            if (typeof grecaptcha !== 'undefined' && grecaptcha.reset) grecaptcha.reset();
            if (typeof turnstile !== 'undefined' && turnstile.reset) turnstile.reset();

            clearInterval(timerInterval);
            $timerDisplay.hide();
            $resendBtn.hide();
        }

        function startTimer(duration = 60) {
            let timer = duration;
            $countdownSpan.text(timer);
            $timerDisplay.show();
            $resendBtn.hide();
            $sendOtpBtn.prop('disabled', true);

            timerInterval = setInterval(function() {
                timer--;
                $countdownSpan.text(timer);
                if (timer <= 0) {
                    clearInterval(timerInterval);
                    $timerDisplay.hide();
                    $resendBtn.show().prop('disabled', false);
                }
            }, 1000);
        }

        $backBtn.on('click', function(e) {
            e.preventDefault();
            resetForm();
        });

        $resendBtn.on('click', function(e) {
            e.preventDefault();
            $(this).prop('disabled', true);
            $sendOtpBtn.trigger('click');
        });

        $sendOtpBtn.on('click', async function(e) {
            e.preventDefault();
            identifierValue = $identifierField.val();
            if (!identifierValue) {
                displayMessage('لطفا ایمیل یا شماره تلفن خود را وارد کنید.', 'error');
                return;
            }

            const originalButtonText = $(this).text();
            $(this).prop('disabled', true).html(slr_public_data.text_sending_otp);
            displayMessage('در حال ارسال کد...', 'info');

            const formData = new URLSearchParams();
            formData.append('action', 'slr_send_otp');
            formData.append('security', slr_public_data.send_otp_nonce);
            formData.append('identifier', identifierValue);

            const recaptchaToken = $form.find('[name="g-recaptcha-response"]').val();
            if (recaptchaToken) formData.append('g-recaptcha-response', recaptchaToken);
            
            const turnstileToken = $form.find('[name="cf-turnstile-response"]').val();
            if (turnstileToken) formData.append('cf-turnstile-response', turnstileToken);

            try {
                const response = await fetch(slr_public_data.ajax_url, { method: 'POST', body: formData });
                const result = await response.json();
                if (result.success) {
                    displayMessage(result.data.message, 'success');
                    $identifierRow.slideUp();
                    $captchaRow.slideUp();
                    $orDivider.slideUp();
                    $socialRow.slideUp();
                    $otpRow.slideDown().find('input').focus();
                    $submitRow.slideDown();
                    $identifierField.prop('readonly', true);
                    startTimer(60);
                } else {
                    throw new Error(result.data.message || 'خطای ناشناخته رخ داد.');
                }
            } catch (error) {
                displayMessage(error.message, 'error');
                $(this).prop('disabled', false).html(originalButtonText);
                resetForm();
            }
        });

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
    } // <-- *** THIS WAS THE MISSING BRACE ***

    /**
     * ================================================================
     * Telegram Login Handler
     * ================================================================
     */
    function initializeTelegramLogin() {
        let pollingInterval;
        let timerInterval;

        function showTelegramModal(bot_link, unique_key, session_id) {
            $('.slr-modal-overlay').remove();
            const modalHTML = `
                <div class="slr-modal-overlay" id="slr-telegram-modal">
                    <div class="slr-modal-content">
                        <a href="#" class="slr-modal-close">&times;</a>
                        <h3>ورود با تلگرام</h3>
                        <p>۱. کد QR را با گوشی خود اسکن کنید، یا<br>۲. کد یکتا را کپی و برای ربات خود ارسال کنید.</p>
                        <div class="slr-modal-qr-code">
                            <img src="https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=${encodeURIComponent(bot_link)}" alt="Telegram QR Code">
                        </div>
                        <div>
                            <p style="margin-bottom: 5px;">کد یکتا:</p>
                            <div class="slr-modal-unique-key">${unique_key}</div>
                        </div>
                        <div class="slr-modal-status">در انتظار تایید از طریق تلگرام...</div>
                        <div class="slr-modal-timer">این درخواست تا <b>5:00</b> دیگر معتبر است.</div>
                    </div>
                </div>
            `;
            $('body').append(modalHTML);
            const $modal = $('#slr-telegram-modal');
            setTimeout(() => $modal.addClass('visible'), 50);

            startPolling(session_id, $modal);
            startTimer(300, $modal);

            $modal.on('click', '.slr-modal-close', function(e) {
                e.preventDefault();
                closeModal($modal);
            });
            $modal.on('click', function(e) {
                if ($(e.target).is($modal)) {
                    closeModal($modal);
                }
            });
        }

        function closeModal($modal) {
            clearInterval(pollingInterval);
            clearInterval(timerInterval);
            $modal.removeClass('visible');
            setTimeout(() => $modal.remove(), 300);
        }

        function startTimer(duration, $modal) {
            let timer = duration;
            const $timerDisplay = $modal.find('.slr-modal-timer b');
            timerInterval = setInterval(function() {
                const minutes = parseInt(timer / 60, 10);
                const seconds = parseInt(timer % 60, 10);
                const displayMinutes = minutes < 10 ? "0" + minutes : minutes;
                const displaySeconds = seconds < 10 ? "0" + seconds : seconds;
                $timerDisplay.text(displayMinutes + ":" + displaySeconds);
                if (--timer < 0) {
                    clearInterval(timerInterval);
                    clearInterval(pollingInterval);
                    $modal.find('.slr-modal-status').text('این درخواست منقضی شده است. لطفا دوباره تلاش کنید.').addClass('error');
                }
            }, 1000);
        }

        function startPolling(session_id, $modal) {
            const $statusArea = $modal.find('.slr-modal-status');
            pollingInterval = setInterval(async function() {
                const formData = new URLSearchParams();
                formData.append('action', 'slr_check_telegram_login_status');
                formData.append('security', slr_public_data.telegram_polling_nonce);
                formData.append('session_id', session_id);
                try {
                    const response = await fetch(slr_public_data.ajax_url, { method: 'POST', body: formData });
                    const result = await response.json();
                    if (result.success) {
                        if (result.data.status === 'success') {
                            clearInterval(pollingInterval);
                            clearInterval(timerInterval);
                            $statusArea.text('ورود موفقیت‌آمیز بود! در حال انتقال...').removeClass('error').addClass('success');
                            window.location.href = result.data.redirect_url;
                        } else if (result.data.status === 'failed') {
                            clearInterval(pollingInterval);
                            clearInterval(timerInterval);
                            $statusArea.text('حسابی با این شماره تلفن یافت نشد.').addClass('error');
                        } else if (result.data.status === 'expired') {
                            clearInterval(pollingInterval);
                            clearInterval(timerInterval);
                            $statusArea.text('این درخواست منقضی شده است.').addClass('error');
                        }
                    } else {
                        clearInterval(pollingInterval);
                        clearInterval(timerInterval);
                    }
                } catch (error) {
                    clearInterval(pollingInterval);
                    clearInterval(timerInterval);
                    $statusArea.text('خطا در برقراری ارتباط با سرور.').addClass('error');
                }
            }, 3000);
        }

        $('body').on('click', '.slr-telegram-button', async function(e) {
            e.preventDefault();
            const $button = $(this);
            const originalText = $button.html();
            $button.prop('disabled', true).html('در حال آماده‌سازی...');
            const formData = new URLSearchParams();
            formData.append('action', 'slr_generate_telegram_request');
            formData.append('security', slr_public_data.telegram_request_nonce);
            try {
                const response = await fetch(slr_public_data.ajax_url, { method: 'POST', body: formData });
                const result = await response.json();
                if (result.success) {
                    showTelegramModal(result.data.bot_link, result.data.unique_key, result.data.session_id);
                } else {
                    alert(result.data.message || 'An error occurred.');
                }
            } catch (error) {
                alert('Could not connect to the server. Please try again.');
            } finally {
                $button.prop('disabled', false).html(originalText);
            }
        });
    }

    /**
     * ================================================================
     * Bale Login Handler
     * ================================================================
     */
    function initializeBaleLogin() {
        // Re-usable variables for modals and polling
        let pollingInterval, timerInterval;

        const closeModal = ($modal) => {
            if(pollingInterval) clearInterval(pollingInterval);
            if(timerInterval) clearInterval(timerInterval);
            if ($modal && $modal.length) {
                $modal.removeClass('visible');
                setTimeout(() => $modal.remove(), 300);
            }
        };

        const showFinalLogin = ($modal, phone) => {
            const finalHTML = `
                <h4>کد تایید را وارد کنید</h4>
                <p>کدی که به اپلیکیشن بله شما ارسال شد را وارد کنید.</p>
                <div class="slr-form-row">
                    <input type="text" class="slr-input slr-otp-input" id="bale_otp_code_input" placeholder="کد تایید" autocomplete="off">
                </div>
                <div class="slr-form-row">
                    <button class="slr-button" id="bale_otp_submit">ورود</button>
                </div>
                <div class="slr-message-area"></div>`;
            $modal.find('.slr-modal-body').html(finalHTML);
            
            $('#bale_otp_submit').on('click', function() {
                const otp = $('#bale_otp_code_input').val();
                // We reuse the main OTP form's submission logic by populating its fields and triggering a submit
                const $mainForm = $('.slr-otp-form');
                $mainForm.find('.slr-identifier-input').val(phone);
                $mainForm.find('.slr-otp-input').val(otp);
                $mainForm.trigger('submit');
                closeModal($modal);
            });
        };
        
        const showBaleOTPForm = ($modal) => {
            const otpHTML = `
                <h4>ورود با کد یکبارمصرف</h4>
                <p>شماره تلفن همراه خود را وارد کنید تا کد تایید از طریق بله برایتان ارسال شود.</p>
                <div class="slr-form-row">
                    <input type="tel" class="slr-input" id="bale_phone_input" placeholder="مثال: 09123456789">
                </div>
                <div class="slr-form-row">
                    <button class="slr-button" id="bale_send_otp_btn">ارسال کد</button>
                </div>
                <div class="slr-message-area"></div>`;
            $modal.find('.slr-modal-body').html(otpHTML);

            $('#bale_send_otp_btn').on('click', async function() {
                const phone = $('#bale_phone_input').val();
                if(!phone) {
                    $modal.find('.slr-message-area').text('شماره تلفن الزامی است.').addClass('slr-error').slideDown();
                    return;
                }
                $(this).prop('disabled', true).text('در حال ارسال...');
                
                const formData = new URLSearchParams();
                formData.append('action', 'slr_send_bale_otp');
                formData.append('security', slr_public_data.bale_otp_nonce);
                formData.append('phone_number', phone);
                
                const response = await fetch(slr_public_data.ajax_url, { method: 'POST', body: formData });
                const result = await response.json();
                if(result.success) {
                    showFinalLogin($modal, phone);
                } else {
                    $modal.find('.slr-message-area').text(result.data.message).addClass('slr-error').slideDown();
                    $(this).prop('disabled', false).text('ارسال کد');
                }
            });
        };

        const showBaleSmartForm = async ($modal) => {
            $modal.find('.slr-modal-body').html('<p>در حال ساخت کد ورود هوشمند...</p>');
            const formData = new URLSearchParams();
            formData.append('action', 'slr_generate_bale_bot_request');
            formData.append('security', slr_public_data.bale_bot_nonce);

            const response = await fetch(slr_public_data.ajax_url, { method: 'POST', body: formData });
            const result = await response.json();
            
            if(result.success) {
                const { unique_code, session_id } = result.data;
                const smartHTML = `
                    <h4>ورود هوشمند با ربات</h4>
                    <p>۱. ربات ما را در بله باز کنید.<br>۲. کد زیر را برای ربات ارسال کنید.</p>
                    <div class="slr-modal-unique-key">${unique_code}</div>
                    <div class="slr-modal-status">در انتظار تایید از طریق بله...</div>
                    <div class="slr-modal-timer">این درخواست تا <b>5:00</b> دیگر معتبر است.</div>`;
                $modal.find('.slr-modal-body').html(smartHTML);
                // The polling and timer functions from Telegram can be adapted for Bale
                // startBalePolling(session_id, $modal); 
                // startBaleTimer(300, $modal);
            } else {
                $modal.find('.slr-modal-body').html(`<p style="color:red;">${result.data.message}</p>`);
            }
        };

        // Main Click Handler for the Bale Button
        $('body').on('click', '.slr-bale-button', function(e) {
            e.preventDefault();
            const mode = $(this).closest('.slr-form-fields-container').data('bale-mode');
            
            // Base Modal Structure
            const modalHTML = `
                <div class="slr-modal-overlay" id="slr-bale-modal">
                    <div class="slr-modal-content">
                        <a href="#" class="slr-modal-close">&times;</a>
                        <div class="slr-modal-body"></div>
                    </div>
                </div>`;
            $('body').append(modalHTML);
            const $modal = $('#slr-bale-modal');
            setTimeout(() => $modal.addClass('visible'), 50);

            if (mode === 'both') {
                const choiceHTML = `
                    <h4>انتخاب روش ورود با بله</h4>
                    <p>یکی از روش‌های زیر را برای ادامه انتخاب کنید.</p>
                    <div class="slr-form-row">
                        <button class="slr-button" id="bale-choice-smart">ورود هوشمند (با ربات)</button>
                    </div>
                    <div class="slr-form-row">
                        <button class="slr-button" id="bale-choice-otp">ورود با کد تایید (OTP)</button>
                    </div>`;
                $modal.find('.slr-modal-body').html(choiceHTML);

                $('#bale-choice-smart').on('click', () => showBaleSmartForm($modal));
                $('#bale-choice-otp').on('click', () => showBaleOTPForm($modal));

            } else if (mode === 'smart_only') {
                showBaleSmartForm($modal);
            } else if (mode === 'otp_only') {
                showBaleOTPForm($modal);
            }

            // Generic modal close events
            $modal.on('click', '.slr-modal-close', (e) => { e.preventDefault(); closeModal($modal); });
            $modal.on('click', (e) => { if ($(e.target).is($modal)) closeModal($modal); });
        });
    }


    /**
     * ================================================================
     * Execution
     * ================================================================
     */
    // Initialize OTP form handlers
    $('.slr-otp-form-container').each(function() {
        initializeForm(this);
    });

    // Initialize our new Telegram login handler
    initializeTelegramLogin();
    initializeBaleLogin();

});