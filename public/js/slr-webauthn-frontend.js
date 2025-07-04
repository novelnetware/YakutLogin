jQuery(document).ready(function($) {
    
    // از یک کلاس برای پیدا کردن تمام دکمه‌های ثبت دستگاه استفاده می‌کنیم.
    const registerButtons = $('.slr-frontend-register-btn');

    // اگر هیچ دکمه‌ای در صفحه وجود نداشت، ادامه نده.
    if (registerButtons.length === 0) {
        return;
    }
    
    /**
     * Helper functions for WebAuthn data conversion.
     */
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

    // رویداد کلیک را به تمام دکمه‌های پیدا شده متصل می‌کنیم.
    registerButtons.on('click', async function(e) {
        e.preventDefault(); // جلوگیری از رفتار پیش‌فرض (مخصوصا اگر دکمه داخل تگ a باشد)

        const $button = $(this);
        // کانتینر پیام مربوط به همین دکمه را پیدا می‌کند.
        const $messageArea = $button.parent().find('.slr-webauthn-message'); 
        
        const originalButtonHTML = $button.html(); // ذخیره محتوای اصلی دکمه
        $button.prop('disabled', true).html('<i class="fas fa-spinner fa-spin" style="margin-right: 8px;"></i>در حال آماده‌سازی...');
        $messageArea.text('').removeClass('success error').slideUp();

        try {
            // 1. Get challenge from the server
            const response = await fetch(slr_webauthn_data.ajax_url, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `action=yakutlogin_get_registration_options&nonce=${slr_webauthn_data.get_options_nonce}`
            });
            const optionsJson = await response.json();
            if (!optionsJson.success) {
                throw new Error(optionsJson.data.message || 'خطا در دریافت تنظیمات از سرور.');
            }

            // 2. Prepare options and call browser API
            const credentialOptions = prepareOptionsForBrowser(optionsJson.data);
            const credential = await navigator.credentials.create({ publicKey: credentialOptions });
            const preparedCredential = prepareCredentialForServer(credential);

            // 3. Send the result to the server for verification
            const verifyUrl = `${slr_webauthn_data.ajax_url}?action=yakutlogin_verify_registration&_wpnonce=${slr_webauthn_data.verify_nonce}`;
            const verifyResponse = await fetch(verifyUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(preparedCredential)
            });
            const verifyResult = await verifyResponse.json();
            if (!verifyResult.success) {
                throw new Error(verifyResult.data.message || 'سرور قادر به تایید دستگاه نبود.');
            }
            
            $messageArea.text('دستگاه شما با موفقیت ثبت شد!').addClass('success').slideDown();
            $button.html('<i class="fas fa-check" style="margin-right: 8px;"></i>ثبت موفق');

        } catch (err) {
            const errorMessage = err.name === 'NotAllowedError' 
                ? 'عملیات توسط شما لغو شد.' 
                : (err.message || 'یک خطای ناشناخته رخ داد.');
            
            $messageArea.text('خطا: ' + errorMessage).addClass('error').slideDown();
            $button.prop('disabled', false).html(originalButtonHTML);
        }
    });
});