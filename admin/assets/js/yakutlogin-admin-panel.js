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
    
    // اگر gatewayId وجود نداشت، محتوا را خالی کن و تمام
    if (!gatewayId) {
        container.innerHTML = '';
        return;
    }

    // ۱. اسپینر را نمایش بده و محتوا را تار کن
    startLoading(); 
    
    fetch(yakutlogin_admin_ajax.ajax_url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `action=yakutlogin_get_gateway_fields&nonce=${yakutlogin_admin_ajax.nonce}&gateway_id=${gatewayId}`
    })
    .then(response => {
        if (!response.ok) {
            // در صورتی که پاسخ سرور خطا بود (مثلا خطای 500)
            throw new Error('Network response was not ok.');
        }
        return response.json();
    })
    .then(result => {
        if (result.success) {
            container.innerHTML = result.data.html;
        } else {
            container.innerHTML = '<div class="setting-option"><p style="color:red;">خطا در بارگذاری فیلدها.</p></div>';
        }
    })
    .catch(error => {
        // این بخش برای خطاهای شبکه یا خطاهای غیرمنتظره است
        console.error('Fetch Error:', error);
        container.innerHTML = '<div class="setting-option"><p style="color:red;">خطای شبکه در ارتباط با سرور.</p></div>';
    })
    .finally(() => {
        // ۲. در هر صورت (موفقیت یا خطا)، اسپینر را پنهان کن
        stopLoading(); 
    });
}

// این توابع باید در دسترس باشند
const mainContent = document.querySelector('.main-content');

function startLoading() {
    if (mainContent) mainContent.classList.add('is-loading');
}

function stopLoading() {
    if (mainContent) mainContent.classList.remove('is-loading');
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

     // 7. Telegram Connection Test
    const testTelegramBtn = document.getElementById('yakutlogin-test-telegram');
    if (testTelegramBtn) {
        testTelegramBtn.addEventListener('click', function() {
            const button = this;
            const statusEl = document.getElementById('telegram-test-status');
            const botTokenField = document.querySelector('input[name="telegram_bot_token"]');
            
            button.disabled = true;
            statusEl.textContent = 'Testing connection...';
            statusEl.style.color = '#0073aa';

            const formData = new URLSearchParams();
            formData.append('action', 'yakutlogin_test_telegram_connection');
            formData.append('nonce', yakutlogin_admin_ajax.nonce);
            formData.append('bot_token', botTokenField.value);

            fetch(yakutlogin_admin_ajax.ajax_url, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    statusEl.textContent = `${result.data.message} Bot: ${result.data.bot_name} (@${result.data.bot_username})`;
                    statusEl.style.color = '#28a745';
                    // Optionally update the readonly username field on the fly
                    const usernameField = document.querySelector('input[value*="@"]');
                    if(usernameField) usernameField.value = `@${result.data.bot_username}`;

                } else {
                    statusEl.textContent = `Error: ${result.data.message}`;
                    statusEl.style.color = '#dc3545';
                }
            })
            .catch(error => {
                statusEl.textContent = 'A network error occurred.';
                statusEl.style.color = '#dc3545';
            })
            .finally(() => {
                button.disabled = false;
            });
        });
    }

    // 8. Cloudflare Worker Creation
    const createWorkerBtn = document.getElementById('yakutlogin-create-worker');
    if (createWorkerBtn) {
        createWorkerBtn.addEventListener('click', function() {
            const button = this;
            const statusEl = document.getElementById('worker-status');
            const cfAccountIdField = document.getElementById('cf_account_id');
            const cfApiTokenField = document.getElementById('cf_api_token');

            if (!cfAccountIdField.value || !cfApiTokenField.value) {
                statusEl.textContent = 'Please provide both Account ID and API Token.';
                statusEl.style.color = '#dc3545';
                return;
            }

            button.disabled = true;
            button.textContent = 'Processing...';
            statusEl.textContent = 'Creating worker and setting webhook. This may take a moment...';
            statusEl.style.color = '#0073aa';

            const formData = new URLSearchParams();
            formData.append('action', 'yakutlogin_create_cf_worker');
            formData.append('nonce', yakutlogin_admin_ajax.nonce);
            formData.append('cf_account_id', cfAccountIdField.value);
            formData.append('cf_api_token', cfApiTokenField.value);
            
            // Clear the token field for security after getting its value
            cfApiTokenField.value = '';

            fetch(yakutlogin_admin_ajax.ajax_url, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    statusEl.textContent = result.data.message;
                    statusEl.style.color = '#28a745';
                    // Force a page reload to show the new webhook URL
                    setTimeout(() => window.location.reload(), 2000);
                } else {
                    statusEl.textContent = `Error: ${result.data.message}`;
                    statusEl.style.color = '#dc3545';
                }
            })
            .catch(error => {
                statusEl.textContent = 'A network error occurred.';
                statusEl.style.color = '#dc3545';
            })
            .finally(() => {
                button.disabled = false;
                button.textContent = 'Create/Update Worker & Set Webhook';
            });
        });
    }
     // 9. Telegram Webhook Debugging
    const checkWebhookBtn = document.getElementById('yakutlogin-check-webhook');
    if (checkWebhookBtn) {
        checkWebhookBtn.addEventListener('click', function() {
            const button = this;
            const statusEl = document.getElementById('webhook-debug-info');

            button.disabled = true;
            statusEl.style.display = 'block';
            statusEl.textContent = 'Fetching webhook status from Telegram...';

            const formData = new URLSearchParams();
            formData.append('action', 'yakutlogin_get_telegram_webhook_info');
            formData.append('nonce', yakutlogin_admin_ajax.nonce);

            fetch(yakutlogin_admin_ajax.ajax_url, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(result => {
                // Pretty-print the JSON response for readability
                statusEl.textContent = JSON.stringify(result, null, 2);
            })
            .catch(error => {
                statusEl.textContent = 'A network error occurred while fetching data.';
            })
            .finally(() => {
                button.disabled = false;
            });
        });
    }
    // 10. Set Telegram Webhook
    const setWebhookBtn = document.getElementById('yakutlogin-set-webhook');
    if (setWebhookBtn) {
        setWebhookBtn.addEventListener('click', function() {
            const button = this;
            const statusEl = document.getElementById('set-webhook-status');

            button.disabled = true;
            button.textContent = 'Setting...';
            statusEl.textContent = 'Sending request to Telegram...';
            statusEl.style.color = '#0073aa';

            const formData = new URLSearchParams();
            formData.append('action', 'yakutlogin_set_telegram_webhook');
            formData.append('nonce', yakutlogin_admin_ajax.nonce);

            fetch(yakutlogin_admin_ajax.ajax_url, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(result => {
                if(result.success) {
                    statusEl.textContent = `Success: ${result.data.message}`;
                    statusEl.style.color = '#28a745';
                } else {
                    statusEl.textContent = `Error: ${result.data.message}`;
                    statusEl.style.color = '#dc3545';
                }
            })
            .catch(error => {
                statusEl.textContent = 'A network error occurred.';
            })
            .finally(() => {
                button.disabled = false;
                button.textContent = 'تنظیم این آدرس به عنوان وبهوک';
            });
        });
    }

    // 11. Bale Login Mode Conditional Display
    const baleModeSelect = document.getElementById('bale_login_mode_select');
    if (baleModeSelect) {
        const manageBaleVisibility = () => {
            const selectedMode = baleModeSelect.value;
            document.querySelectorAll('[data-bale-mode]').forEach(card => {
                const requiredModes = card.getAttribute('data-bale-mode').split(' ');
                if (requiredModes.includes(selectedMode)) {
                    card.style.display = 'block';
                } else {
                    card.style.display = 'none';
                }
            });
        };

        // Run on page load and on change
        manageBaleVisibility();
        baleModeSelect.addEventListener('change', manageBaleVisibility);
    }

     // --- API Key Management ---
    // Only run this code if we are on the YakutLogin settings page
    if (document.getElementById('api-keys-table-body')) {

        const apiKeyTableBody = document.getElementById('api-keys-table-body');

        // Function to fetch and display API keys
        const loadApiKeys = () => {
            apiKeyTableBody.innerHTML = '<tr><td colspan="4">در حال بارگذاری...</td></tr>';
            
            const formData = new URLSearchParams({
                action: 'yakutlogin_get_api_keys',
                nonce: yakutlogin_admin_ajax.nonce
            });

            fetch(yakutlogin_admin_ajax.ajax_url, { method: 'POST', body: formData })
            .then(response => response.json())
            .then(result => {
                apiKeyTableBody.innerHTML = ''; // Clear loading message
                if (result.success && result.data.length > 0) {
                    result.data.forEach(key => {
                        const row = `
                            <tr>
                                <td>${escapeHtml(key.name)}</td>
                                <td><code>${escapeHtml(key.public_key)}</code></td>
                                <td>${new Date(key.created_at.replace(' ', 'T')).toLocaleDateString('fa-IR')}</td>
                                <td>
                                    <button class="button button-small button-danger revoke-api-key-btn" data-key-id="${key.id}" ${key.status === 'revoked' ? 'disabled' : ''}>
                                        ${key.status === 'revoked' ? 'باطل شده' : 'باطل کردن'}
                                    </button>
                                </td>
                            </tr>`;
                        apiKeyTableBody.insertAdjacentHTML('beforeend', row);
                    });
                } else {
                    apiKeyTableBody.innerHTML = '<tr><td colspan="4">هیچ کلیدی یافت نشد.</td></tr>';
                }
            });
        };
        
        // Helper function to prevent XSS
        const escapeHtml = (unsafe) => {
            return unsafe
                 .replace(/&/g, "&amp;")
                 .replace(/</g, "&lt;")
                 .replace(/>/g, "&gt;")
                 .replace(/"/g, "&quot;")
                 .replace(/'/g, "&#039;");
         }

        // Generate New Key Button Handler
        const generateBtn = document.getElementById('yakutlogin-generate-api-key');
        generateBtn.addEventListener('click', function() {
            const keyNameField = document.getElementById('new_api_key_name');
            const keyName = keyNameField.value.trim();

            if (!keyName) {
                alert('لطفا یک نام برای کلید وارد کنید.');
                return;
            }

            this.disabled = true;
            this.textContent = 'در حال ایجاد...';
            
            const formData = new URLSearchParams({
                action: 'yakutlogin_generate_api_key',
                nonce: yakutlogin_admin_ajax.nonce,
                key_name: keyName
            });

            fetch(yakutlogin_admin_ajax.ajax_url, { method: 'POST', body: formData })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    document.getElementById('new_public_key_display').value = result.data.public_key;
                    document.getElementById('new_secret_key_display').value = result.data.secret_key;
                    document.getElementById('slr-api-key-modal').style.display = 'flex';
                    keyNameField.value = ''; // Clear the input field
                    loadApiKeys(); // Refresh the list
                } else {
                    alert('Error: ' + result.data.message);
                }
            })
            .finally(() => {
                this.disabled = false;
                this.textContent = 'ایجاد کلید API جدید';
            });
        });
        
        // Revoke Key Button Handler (using event delegation)
        apiKeyTableBody.addEventListener('click', function(e) {
            if (e.target && e.target.classList.contains('revoke-api-key-btn')) {
                const button = e.target;
                const keyId = button.dataset.keyId;

                if (!confirm('آیا از باطل کردن این کلید API مطمئن هستید؟ این عمل غیرقابل بازگشت است.')) {
                    return;
                }
                
                button.disabled = true;

                const formData = new URLSearchParams({
                    action: 'yakutlogin_revoke_api_key',
                    nonce: yakutlogin_admin_ajax.nonce,
                    key_id: keyId
                });

                fetch(yakutlogin_admin_ajax.ajax_url, { method: 'POST', body: formData })
                .then(response => response.json())
                .then(result => {
                    if (result.success) {
                        button.textContent = 'باطل شده';
                    } else {
                        alert('Error: ' + result.data.message);
                        button.disabled = false;
                    }
                });
            }
        });

        // Modal close handler
        const apiKeyModal = document.getElementById('slr-api-key-modal');
        apiKeyModal.addEventListener('click', function(e) {
            if (e.target.classList.contains('slr-modal-overlay') || e.target.classList.contains('slr-modal-close')) {
                this.style.display = 'none';
            }
        });
        
        // Initial load of keys
        loadApiKeys();
    }

     // --- Digits Importer ---
    const importDigitsDryRunBtn = document.getElementById('yakutlogin-import-digits-dry-run');
    const importDigitsStartBtn = document.getElementById('yakutlogin-import-digits-start');
    const importDigitsStatusEl = document.getElementById('import-digits-status');
    const digitsMetaKeyInput = document.getElementById('digits_meta_key_input');

    const handleDigitsImport = (isDryRun) => {
        const button = isDryRun ? importDigitsDryRunBtn : importDigitsStartBtn;
        
        if (!isDryRun) {
            if (!confirm('آیا از شروع فرآیند درون‌ریزی مطمئن هستید؟ این عمل ممکن است بر اساس تعداد کاربران زمان‌بر باشد.')) {
                return;
            }
        }

        button.disabled = true;
        importDigitsStatusEl.style.display = 'block';
        importDigitsStatusEl.textContent = 'در حال پردازش، لطفا صبر کنید...';

        const formData = new URLSearchParams({
            action: 'slr_import_from_digits',
            nonce: yakutlogin_admin_ajax.nonce,
            meta_key: digitsMetaKeyInput.value,
            dry_run: isDryRun,
        });

        fetch(yakutlogin_admin_ajax.ajax_url, { method: 'POST', body: formData })
        .then(response => response.json())
        .then(result => {
            if (result.success) {
                let statusText = result.data.message;
                if(result.data.log) {
                    statusText += "\n\n--- LOG ---\n" + result.data.log;
                }
                importDigitsStatusEl.textContent = statusText;
            } else {
                 importDigitsStatusEl.textContent = 'Error: ' + result.data.message;
            }
        })
        .finally(() => {
            button.disabled = false;
        });
    };

    if (importDigitsDryRunBtn) {
        importDigitsDryRunBtn.addEventListener('click', () => handleDigitsImport(true));
    }
    if (importDigitsStartBtn) {
        importDigitsStartBtn.addEventListener('click', () => handleDigitsImport(false));
    }

     // --- WooCommerce Importer ---
    const importWCDryRunBtn = document.getElementById('yakutlogin-import-wc-dry-run');
    const importWCStartBtn = document.getElementById('yakutlogin-import-wc-start');
    const importWCStatusEl = document.getElementById('import-wc-status');

    const handleWCImport = (isDryRun) => {
        // Ensure the elements exist before proceeding
        if (!importWCStatusEl) return;
        
        const button = isDryRun ? importWCDryRunBtn : importWCStartBtn;
        
        if (!isDryRun) {
            if (!confirm('آیا از شروع فرآیند درون‌ریزی شماره‌های مشتریان ووکامرس مطمئن هستید؟')) {
                return;
            }
        }

        button.disabled = true;
        importWCStatusEl.style.display = 'block';
        importWCStatusEl.textContent = 'در حال پردازش، لطفا صبر کنید... این فرآیند ممکن است زمان‌بر باشد.';

        const formData = new URLSearchParams({
            action: 'slr_import_from_wc',
            nonce: yakutlogin_admin_ajax.nonce,
            dry_run: isDryRun,
        });

        fetch(yakutlogin_admin_ajax.ajax_url, { method: 'POST', body: formData })
        .then(response => response.json())
        .then(result => {
            if (result.success) {
                let statusText = result.data.message;
                if(result.data.log) {
                    statusText += "\n\n--- LOG ---\n" + result.data.log;
                }
                importWCStatusEl.textContent = statusText;
            } else {
                 importWCStatusEl.textContent = 'Error: ' + (result.data.message || 'An unknown error occurred.');
            }
        })
        .finally(() => {
            button.disabled = false;
        });
    };

    if (importWCDryRunBtn) {
        importWCDryRunBtn.addEventListener('click', () => handleWCImport(true));
    }
    if (importWCStartBtn) {
        importWCStartBtn.addEventListener('click', () => handleWCImport(false));
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