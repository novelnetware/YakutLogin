document.addEventListener('DOMContentLoaded', () => {
    // مدیریت تعویض تب‌ها
    const navLinks = document.querySelectorAll('.nav-links li');
    navLinks.forEach(link => {
        link.addEventListener('click', () => {
            const contentId = link.getAttribute('data-content');
            
            // مدیریت کلاس active در منو
            navLinks.forEach(l => l.classList.remove('active'));
            link.classList.add('active');

            // نمایش محتوای تب مربوطه
            document.querySelectorAll('.content-section').forEach(content => {
                content.classList.remove('active');
            });
            document.getElementById(contentId).classList.add('active');
        });
    });

    // مدیریت ذخیره تنظیمات با ایجکس
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
                body: `action=yakutlogin_save_settings&nonce=${yakutlogin_admin_ajax.nonce}&settings=${formData}`
            })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    showNotification(result.data.message, 'success');
                } else {
                    showNotification(result.data.message, 'error');
                }
            })
            .catch(error => showNotification('خطای ناشناخته در ارتباط با سرور.', 'error'))
            .finally(() => {
                saveButton.innerHTML = '<i class="fas fa-save"></i> ذخیره تغییرات';
                saveButton.disabled = false;
            });
        });
    }

   // مدیریت بارگذاری داینامیک فیلدهای درگاه پیامک
    const primaryGatewaySelect = document.getElementById('primary-sms-provider-select');
    const backupGatewaySelect = document.getElementById('backup-sms-provider-select');

    if (primaryGatewaySelect) {
        loadGatewayFields(primaryGatewaySelect.value, 'primary');
        primaryGatewaySelect.addEventListener('change', (e) => {
            loadGatewayFields(e.target.value, 'primary');
        });
    }
    
    if (backupGatewaySelect) {
        loadGatewayFields(backupGatewaySelect.value, 'backup');
        backupGatewaySelect.addEventListener('change', (e) => {
            loadGatewayFields(e.target.value, 'backup');
        });
    }

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

    // --- WebAuthn Registration Logic ---
    const registerDeviceBtn = document.getElementById('yakutlogin-register-device');
    if (registerDeviceBtn) {
        registerDeviceBtn.addEventListener('click', async () => {
            registerDeviceBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> منتظر تایید شما...';
            registerDeviceBtn.disabled = true;

            try {
                // ۱. دریافت گزینه‌ها از سرور
                const createOptionsResponse = await fetch(yakutlogin_admin_ajax.ajax_url, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `action=yakutlogin_get_registration_options&nonce=${yakutlogin_admin_ajax.nonce}`
                });
                const creationOptions = await createOptionsResponse.json();
                
                if (!creationOptions.success) {
                    throw new Error(creationOptions.data.message);
                }

                // آماده‌سازی گزینه‌ها برای مرورگر (تبدیل base64url)
                const credentialOptions = prepareOptionsForBrowser(creationOptions.data);

                // ۲. فراخوانی API مرورگر
                const credential = await navigator.credentials.create({
                    publicKey: credentialOptions
                });
                
                // ۳. ارسال مدرک به سرور برای تایید
                const verifyResponse = await fetch(yakutlogin_admin_ajax.ajax_url, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(prepareCredentialForServer(credential))
                });

                const verificationResult = await verifyResponse.json();
                
                if (verificationResult.success) {
                    showNotification(verificationResult.data.message, 'success');
                } else {
                    throw new Error(verificationResult.data.message);
                }

            } catch (err) {
                console.error("WebAuthn Error:", err);
                showNotification(err.message || 'ثبت دستگاه با خطا مواجه شد.', 'error');
            } finally {
                registerDeviceBtn.innerHTML = '<i class="fas fa-fingerprint"></i> ثبت این دستگاه';
                registerDeviceBtn.disabled = false;
            }
        });
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

    // تابع نمایش نوتیفیکیشن
    function showNotification(message, type = 'success') {
        const container = document.getElementById('notification-container');
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
});