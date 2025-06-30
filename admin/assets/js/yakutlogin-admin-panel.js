document.addEventListener('DOMContentLoaded', () => {

    // =================================================================
    // Helper Functions (Moved inside DOMContentLoaded)
    // =================================================================

    /**
     * تابع نمایش نوتیفیکیشن
     */
    function showNotification(message, type = 'success') {
        const container = document.getElementById('notification-container');
        if (!container) {
            console.error('Notification container not found!');
            alert(message); // Fallback to a simple alert
            return;
        }
        const notification = document.createElement('div');
        notification.className = `custom-notification ${type}`;
        notification.innerHTML = `
            <i class="fas fa-${type === 'success' ? 'check-circle' : 'times-circle'}"></i>
            <div class="notification-content"><p>${message}</p></div>
        `;
        container.appendChild(notification);
        setTimeout(() => {
            notification.remove();
        }, 4000);
    }

    /**
     * توابع کمکی برای WebAuthn
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

    /**
     * تابع بارگذاری فیلدهای درگاه پیامک
     */
    function loadGatewayFields(gatewayId, type) { // type can be 'primary' or 'backup'
        const containerId = (type === 'primary') 
            ? 'primary-gateway-fields-container' 
            : 'backup-gateway-fields-container';
            
        const container = document.getElementById(containerId);
        if (!container) return;
        
        container.innerHTML = '<div class="setting-option"><i class="fas fa-spinner fa-spin"></i> در حال بارگذاری فیلدها...</div>';

        if (!gatewayId) {
            container.innerHTML = '';
            return;
        }
        
        fetch(yakutlogin_admin_ajax.ajax_url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=yakutlogin_get_gateway_fields&nonce=${yakutlogin_admin_ajax.nonce}&gateway_id=${gatewayId}`
        })
        .then(response => response.json())
        .then(result => {
            if (result.success) {
                container.innerHTML = result.data.html;
            } else {
                container.innerHTML = '<div class="setting-option"><p style="color:red;">خطا در بارگذاری فیلدها.</p></div>';
            }
        });
    }


    // =================================================================
    // Event Listeners
    // =================================================================

    // ۱. مدیریت تعویض تب‌ها
    const navLinks = document.querySelectorAll('.nav-links li');
    navLinks.forEach(link => {
        link.addEventListener('click', () => {
            const contentId = link.getAttribute('data-content');
            if (!contentId || !document.getElementById(contentId)) return;

            navLinks.forEach(l => l.classList.remove('active'));
            link.classList.add('active');

            document.querySelectorAll('.content-section').forEach(content => {
                content.classList.remove('active');
            });
            document.getElementById(contentId).classList.add('active');
        });
    });

    // ۲. مدیریت ذخیره تنظیمات با ایجکس
    const saveButton = document.getElementById('yakutlogin-save-settings');
    if (saveButton) {
        saveButton.addEventListener('click', (e) => {
            e.preventDefault();
            const form = document.getElementById('yakutlogin-admin-form');
            const formData = new URLSearchParams(new FormData(form)).toString();
            
            saveButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> در حال ذخیره...';
            saveButton.disabled = true;

            fetch(yakutlogin_admin_ajax.ajax_url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=yakutlogin_save_settings&nonce=${yakutlogin_admin_ajax.nonce}&${formData}`
            })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    showNotification(result.data.message, 'success');
                } else {
                    showNotification(result.data.message || 'خطای ناشناخته.', 'error');
                }
            })
            .catch(error => showNotification('خطای ناشناخته در ارتباط با سرور.', 'error'))
            .finally(() => {
                saveButton.innerHTML = '<i class="fas fa-save"></i> ذخیره تغییرات';
                saveButton.disabled = false;
            });
        });
    }

    // ۳. مدیریت بارگذاری داینامیک فیلدهای درگاه پیامک
    const primaryGatewaySelect = document.getElementById('primary-sms-provider-select');
    if (primaryGatewaySelect) {
        loadGatewayFields(primaryGatewaySelect.value, 'primary');
        primaryGatewaySelect.addEventListener('change', (e) => {
            loadGatewayFields(e.target.value, 'primary');
        });
    }
    
    const backupGatewaySelect = document.getElementById('backup-sms-provider-select');
    if (backupGatewaySelect) {
        loadGatewayFields(backupGatewaySelect.value, 'backup');
        backupGatewaySelect.addEventListener('change', (e) => {
            loadGatewayFields(e.target.value, 'backup');
        });
    }

    // ۴. مدیریت ثبت دستگاه با WebAuthn
    const registerDeviceBtn = document.getElementById('yakutlogin-register-device');
    if (registerDeviceBtn) {
        registerDeviceBtn.addEventListener('click', async () => {
            registerDeviceBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> منتظر تایید شما...';
            registerDeviceBtn.disabled = true;

            try {
                // دریافت گزینه‌ها از سرور
                const createOptionsResponse = await fetch(yakutlogin_admin_ajax.ajax_url, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `action=yakutlogin_get_registration_options&nonce=${yakutlogin_admin_ajax.nonce}`
                });
                const creationOptions = await createOptionsResponse.json();
                
                if (!creationOptions.success) {
                    throw new Error(creationOptions.data.message || 'خطا در دریافت تنظیمات ثبت‌نام.');
                }

                // آماده‌سازی گزینه‌ها برای مرورگر
                const credentialOptions = prepareOptionsForBrowser(creationOptions.data);

                // فراخوانی API مرورگر
                const credential = await navigator.credentials.create({
                    publicKey: credentialOptions
                });
                
                // ارسال مدرک به سرور برای تایید
                const verifyResponse = await fetch(yakutlogin_admin_ajax.ajax_url, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(prepareCredentialForServer(credential))
                });

                const verificationResult = await verifyResponse.json();
                
                if (verificationResult.success) {
                    showNotification(verificationResult.data.message, 'success');
                    // Optional: reload page or update UI after successful registration
                    setTimeout(() => window.location.reload(), 1500);
                } else {
                    throw new Error(verificationResult.data.message || 'سرور قادر به تایید دستگاه نبود.');
                }

            } catch (err) {
                console.error("WebAuthn Error:", err);
                showNotification(err.message || 'ثبت دستگاه با خطا مواجه شد. (ممکن است مرورگر شما پشتیبانی نکند یا عملیات لغو شده باشد)', 'error');
            } finally {
                registerDeviceBtn.innerHTML = '<i class="fas fa-fingerprint"></i> ثبت این دستگاه';
                registerDeviceBtn.disabled = false;
            }
        });
    }

});