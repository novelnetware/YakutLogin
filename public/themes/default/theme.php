<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class SLR_Default_Theme implements SLR_Theme {
    private $theme_data;

    public function __construct(array $theme_data) {
        $this->theme_data = $theme_data;
    }
    
    public function get_theme_data(): array {
        return $this->theme_data;
    }
    
    public function get_assets(): array {
        return $this->theme_data['assets'] ?? [];
    }

    /**
     * این تابع کنترل‌های اختصاصی این پوسته را در المنتور ثبت می‌کند.
     */
    public function register_elementor_controls(\Elementor\Widget_Base $widget) {
        $controls_file = $this->theme_data['path'] . ($this->theme_data['elementor_controls'] ?? '');
        
        // بررسی می‌کنیم که آیا فایلی برای کنترل‌ها تعریف شده و وجود دارد یا خیر
        if ( !empty($controls_file) && file_exists($controls_file) ) {
            // با استفاده از require، متغیر $widget در دسترس فایل کنترل‌ها قرار می‌گیرد
            require $controls_file;
        }
    }

    /**
     * این تابع، ساختار HTML فرم را تولید می‌کند.
     * @param array $args داده‌های مورد نیاز از جمله لینک‌ها، کلیدها و متن‌ها.
     * @return string کد HTML نهایی فرم.
     */
    public function get_html(array $args): string {
        ob_start();
        ?>
        
        <div class="slr-form-row slr-identifier-row">
            <?php if ($args['show_labels']): ?>
                <label for="slr_identifier_<?php echo esc_attr($args['form_id']); ?>">ایمیل یا شماره تلفن همراه</label>
            <?php endif; ?>
            <input type="text" name="slr_identifier" class="slr-input slr-identifier-input" placeholder="مثال: 09123456789 یا user@example.com" />
        </div>

        <div class="slr-form-row slr-send-otp-row">
            <button type="button" class="slr-button slr-send-otp-button">
                <?php echo esc_html( $args['button_texts']['send_otp'] ); ?>
            </button>
        </div>

        <div class="slr-form-row slr-otp-row" style="display: none;">
            <?php if ($args['show_labels']): ?>
                <label for="slr_otp_code_<?php echo esc_attr($args['form_id']); ?>">کد یکبار مصرف</label>
            <?php endif; ?>
            <input type="text" name="slr_otp_code" class="slr-input slr-otp-input" placeholder="کد تایید" autocomplete="off" />
        </div>
        
        <?php if ($args['captcha_type'] !== 'none' && !empty($args['captcha_site_key'])): ?>
            <div class="slr-form-row slr-captcha-row">
                <?php if ($args['captcha_type'] === 'recaptcha_v2'): ?>
                    <div class="g-recaptcha" data-sitekey="<?php echo esc_attr($args['captcha_site_key']); ?>"></div>
                <?php elseif ($args['captcha_type'] === 'turnstile'): ?>
                    <div class="cf-turnstile" data-sitekey="<?php echo esc_attr($args['captcha_site_key']); ?>"></div>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <div class="slr-form-row slr-submit-row" style="display: none;">
            <button type="submit" name="slr_submit" class="slr-button slr-submit-button">
                <?php echo esc_html( $args['button_texts']['submit'] ); ?>
            </button>
        </div>
        
        <?php if ($args['google_login_enabled'] || $args['webauthn_enabled']): ?>
            <div class="slr-or-divider">یا</div>
        <?php endif; ?>

        <div class="slr-social-login-row">
            <?php if ($args['google_login_enabled']): ?>
                <a href="<?php echo esc_url($args['google_login_url']); ?>" class="slr-button slr-google-button">
                    <i class="fab fa-google"></i> <span><?php echo esc_html($args['button_texts']['google']); ?></span>
                </a>
            <?php endif; ?>

            <?php if ($args['webauthn_enabled']): ?>
                <button type="button" class="slr-button slr-webauthn-login-button" style="display:none;">
                    <i class="fas fa-fingerprint"></i> <span><?php echo esc_html($args['button_texts']['webauthn']); ?></span>
                </button>
            <?php endif; ?>
        </div>

        <?php
        return ob_get_clean();
    }
}