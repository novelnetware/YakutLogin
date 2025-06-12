<?php
/**
 * View for the new admin panel.
 *
 * @package    Sms_Login_Register
 * @subpackage Sms_Login_Register/admin/partials
 */

// دریافت تنظیمات ذخیره شده برای نمایش مقادیر فعلی در فیلدها
$options = get_option( 'slr_plugin_options', [] );
?>
<div class="yakutlogin-admin-wrapper">
    <div id="notification-container"></div>

    <div class="dashboard">
        <nav class="sidebar">
            <div class="logo">
                <div class="logo-icon"><i class="fas fa-key"></i></div>
                <span>یاقوت لاگین</span>
            </div>
            <ul class="nav-links">
                <li class="active" data-content="general-settings-content">
                    <i class="fas fa-cogs"></i>
                    <span>تنظیمات عمومی</span>
                    <div class="nav-indicator"></div>
                </li>
                <li data-content="sms-gateway-content">
                    <i class="fas fa-paper-plane"></i>
                    <span>درگاه پیامک</span>
                    <div class="nav-indicator"></div>
                </li>
                <li data-content="google-captcha-content">
                    <i class="fab fa-google"></i>
                    <span>ورود با گوگل و کپچا</span>
                    <div class.nav-indicator"></div>
                </li>
                <li data-content="integrations-content">
                    <i class="fas fa-puzzle-piece"></i>
                    <span>یکپارچه‌سازی</span>
                    <div class="nav-indicator"></div>
                </li>
                 <li data-content="pro-features-content">
                    <i class="fas fa-star"></i>
                    <span>امکانات حرفه‌ای</span>
                    <div class="nav-indicator"></div>
                </li>
            </ul>
        </nav>

        <main class="main-content">
            <form id="yakutlogin-admin-form">
                <div id="general-settings-content" class="content-section active">
                    <div class="section-header">
                        <h2>تنظیمات عمومی</h2>
                        <p>تنظیمات اصلی افزونه یاقوت لاگین</p>
                    </div>
                    <div class="settings-grid">
                        <div class="settings-card">
                            <h3>تنظیمات ایمیل</h3>
                            <div class="setting-option">
                                <span>فعال‌سازی کد با ایمیل</span>
                                <?php $this->render_setting_field('email_otp_enabled', 'checkbox', $options); ?>
                            </div>
                            <div class="setting-option">
                                <label>موضوع ایمیل کد یکبارمصرف</label>
                                <?php $this->render_setting_field('otp_email_subject', 'text', $options, 'کد تایید شما'); ?>
                            </div>
                             <div class="setting-option">
                                <label>متن ایمیل کد یکبارمصرف</label>
                                <?php $this->render_setting_field('otp_email_body', 'textarea', $options, "کد تایید شما: {otp_code}"); ?>
                                <small>از {otp_code}, {site_title}, {site_url} استفاده کنید.</small>
                            </div>
                        </div>
                    </div>
                </div>

                // In admin/partials/yakutlogin-admin-panel-display.php

<div id="sms-gateway-content" class="content-section">
    <div class="section-header">
        <h2>درگاه پیامک</h2>
        <p>درگاه پیامک اصلی و پشتیبان را انتخاب و پیکربندی کنید.</p>
    </div>
    <div class="settings-grid">
        <div class="settings-card">
             <h3>درگاه اصلی</h3>
             <div class="setting-option">
                <label>سرویس‌دهنده اصلی</label>
                <?php $this->render_setting_field('sms_provider', 'select_gateway', $options); ?>
             </div>
             <div id="primary-gateway-fields-container"></div>
        </div>
        
        <div class="settings-card">
             <h3>درگاه پشتیبان (Fallback)</h3>
             <div class="setting-option">
                <label>سرویس‌دهنده پشتیبان</label>
                <?php $this->render_setting_field('sms_provider_backup', 'select_gateway_backup', $options); ?>
             </div>
             <div class="setting-option">
                <small>اگر ارسال با درگاه اصلی ناموفق بود، سیستم به طور خودکار از این درگاه استفاده می‌کند.</small>
             </div>
             <div id="backup-gateway-fields-container"></div>
        </div>
    </div>
</div>

                <div id="google-captcha-content" class="content-section">
                    </div>
                <div id="integrations-content" class="content-section">
                    </div>
                 <div id="pro-features-content" class="content-section">
    <div class="section-header">
        <h2>امکانات حرفه‌ای</h2>
        <p>ورود بدون رمز و سایر قابلیت‌های پیشرفته را مدیریت کنید.</p>
    </div>
    <div class="settings-grid">
        <div class="settings-card">
            <h3>ورود با اثر انگشت / چهره (WebAuthn)</h3>
            <div class="setting-option">
                <span>فعال‌سازی ورود بیومتریک</span>
                <?php $this->render_setting_field('webauthn_enabled', 'checkbox', $options); ?>
            </div>
            <div class="setting-option">
                <p>با کلیک بر روی دکمه زیر، می‌توانید این دستگاه را برای ورود سریع و امن به حساب کاربری خود (<?php echo esc_html(wp_get_current_user()->user_login); ?>) ثبت کنید.</p>
            </div>
            <div class="setting-option">
                 <button id="yakutlogin-register-device" class="filter-button"><i class="fas fa-fingerprint"></i>ثبت این دستگاه</button>
            </div>
             <div id="webauthn-devices-list">
                </div>
        </div>
    </div>
</div>
                    </div>

            </form>
            
            <div class="save-button-container">
                 <button id="yakutlogin-save-settings" class="filter-button"><i class="fas fa-save"></i>ذخیره تغییرات</button>
            </div>
        </main>
    </div>
</div>