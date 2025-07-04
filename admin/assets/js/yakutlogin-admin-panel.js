document.addEventListener('DOMContentLoaded', () => {

    // =================================================================
    // Helper Functions
    // =================================================================

    /**
     * Displays a notification.
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
        }, 5000);
    }

    /**
     * WebAuthn helper functions.
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
     * Loads SMS gateway fields via AJAX.
     */
    function loadGatewayFields(gatewayId, type) {
        const containerId = (type === 'primary') 
            ? 'primary-gateway-fields-container' 
            : 'backup-gateway-fields-container';
            
        const container = document.getElementById(containerId);
        if (!container) return;
        
        if (!gatewayId) {
            container.innerHTML = '';
            return;
        }

        container.innerHTML = '<div class="setting-option"><i class="fas fa-spinner fa-spin"></i> در حال بارگذاری فیلدها...</div>';
        
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

    // 1. Tab switching
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

    // 2. Save settings via AJAX
    const saveButton = document.getElementById('yakutlogin-save-settings');
    if (saveButton) {
        saveButton.addEventListener('click', (e) => {
            e.preventDefault();
            const form = document.getElementById('yakutlogin-admin-form');
            const formData = new URLSearchParams(new FormData(form)).toString();
            
            const originalButtonHTML = saveButton.innerHTML;
            saveButton.innerHTML = '<i class="fas fa-spinner fa-spin" style="margin-left: 8px;"></i> در حال ذخیره...';
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
                    showNotification(result.data.message || 'خطای ناشناخته.', 'error');
                }
            })
            .catch(error => showNotification('خطای ناشناخته در ارتباط با سرور.', 'error'))
            .finally(() => {
                saveButton.innerHTML = originalButtonHTML;
                saveButton.disabled = false;
            });
        });
    }

    // 3. Dynamic loading for SMS gateway fields
    const primaryGatewaySelect = document.getElementById('primary-sms-provider-select');
    if (primaryGatewaySelect) {
        loadGatewayFields(primaryGatewaySelect.value, 'primary');
        primaryGatewaySelect.addEventListener('change', (e) => loadGatewayFields(e.target.value, 'primary'));
    }
    
    const backupGatewaySelect = document.getElementById('backup-sms-provider-select');
    if (backupGatewaySelect) {
        loadGatewayFields(backupGatewaySelect.value, 'backup');
        backupGatewaySelect.addEventListener('change', (e) => loadGatewayFields(e.target.value, 'backup'));
    }

    // 4. WebAuthn device registration
    const registerDeviceBtn = document.getElementById('yakutlogin-register-device');
    if (registerDeviceBtn) {
        registerDeviceBtn.addEventListener('click', async () => {
            const originalButtonHTML = registerDeviceBtn.innerHTML;
            registerDeviceBtn.innerHTML = '<i class="fas fa-spinner fa-spin" style="margin-left: 8px;"></i> منتظر تایید شما...';
            registerDeviceBtn.disabled = true;

            try {
                // Get options from the server
                const createOptionsResponse = await fetch(yakutlogin_admin_ajax.ajax_url, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `action=yakutlogin_get_registration_options&nonce=${yakutlogin_admin_ajax.nonce}`
                });
                const creationOptionsJSON = await createOptionsResponse.json();
                
                if (!creationOptionsJSON.success) {
                    throw new Error(creationOptionsJSON.data.message || 'خطا در دریافت تنظیمات ثبت‌نام.');
                }

                // *** اصلاح شد: متغیر credentialOptions تعریف و مقداردهی شد ***
                const credentialOptions = prepareOptionsForBrowser(creationOptionsJSON.data);

                // Call browser API
                const credential = await navigator.credentials.create({
                    publicKey: credentialOptions
                });
                
                // *** اصلاح شد: متغیر preparedCredential تعریف و مقداردهی شد ***
                const preparedCredential = prepareCredentialForServer(credential);

                // Send credential to the server for verification
                // *** اصلاح شد: 'action' و '_wpnonce' به URL اضافه شدند ***
                const verifyUrl = `${yakutlogin_admin_ajax.ajax_url}?action=yakutlogin_verify_registration&_wpnonce=${yakutlogin_admin_ajax.nonce}`;

                const verifyResponse = await fetch(verifyUrl, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(preparedCredential)
                });
                const verificationResult = await verifyResponse.json();
                
                if (verificationResult.success) {
                    showNotification(verificationResult.data.message, 'success');
                    setTimeout(() => window.location.reload(), 2000);
                } else {
                    throw new Error(verificationResult.data.message || 'سرور قادر به تایید دستگاه نبود.');
                }

            } catch (err) {
                console.error("WebAuthn Error:", err);
                const errorMessage = err.name === 'NotAllowedError' 
                    ? 'عملیات توسط شما لغو شد.' 
                    : (err.message || 'یک خطای ناشناخته رخ داد.');
                showNotification(errorMessage, 'error');
            } finally {
                registerDeviceBtn.innerHTML = originalButtonHTML;
                registerDeviceBtn.disabled = false;
            }
        });
    }

    // 5. Dynamic visibility for CAPTCHA provider settings
    const captchaSelect = document.getElementById('captcha-type-select');
    if (captchaSelect) {
        const manageCaptchaVisibility = () => {
            const selectedProvider = captchaSelect.value;
            document.querySelectorAll('[data-captcha-provider]').forEach(card => {
                card.style.display = (card.getAttribute('data-captcha-provider') === selectedProvider) ? 'block' : 'none';
            });
        };
        manageCaptchaVisibility(); // Run on page load
        captchaSelect.addEventListener('change', manageCaptchaVisibility); // Run on change
    }

    // 6. Data cleanup handling
    const cleanupBtn = document.getElementById('yakutlogin-cleanup-data');
    if (cleanupBtn) {
        cleanupBtn.addEventListener('click', function() {
            if (confirm('آیا مطمئن هستید؟ این عمل غیرقابل بازگشت است و تمام داده‌های یاقوت لاگین را حذف می‌کند.')) {
                if (confirm('هشدار نهایی! تمام داده‌ها برای همیشه حذف خواهند شد. ادامه می‌دهید؟')) {
                    
                    const originalButtonText = this.innerHTML;
                    this.disabled = true;
                    this.innerHTML = '<i class="fas fa-spinner fa-spin" style="margin-left: 8px;"></i> در حال پاکسازی...';

                    const formData = new URLSearchParams();
                    formData.append('action', 'yakutlogin_cleanup_data');
                    formData.append('nonce', yakutlogin_admin_ajax.nonce);

                    fetch(yakutlogin_admin_ajax.ajax_url, {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(result => {
                        if (result.success) {
                            alert(result.data.message);
                            window.location.reload();
                        } else {
                            alert('خطا: ' + (result.data.message || 'یک مشکل ناشناخته رخ داد.'));
                            this.disabled = false;
                            this.innerHTML = originalButtonText;
                        }
                    })
                    .catch(error => {
                        console.error('Cleanup Error:', error);
                        alert('خطای شبکه. لطفا اتصال اینترنت خود را بررسی کرده و دوباره تلاش کنید.');
                        this.disabled = false;
                        this.innerHTML = originalButtonText;
                    });
                }
            }
        });
    }
});