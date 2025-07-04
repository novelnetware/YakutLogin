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
            if (window.tinymce) {
    tinymce.triggerSave();
}

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
                body: `action=yakutlogin_save_settings&nonce=${yakutlogin_admin_ajax.nonce}&settings=${encodeURIComponent(formData)}`
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