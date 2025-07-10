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
        // START: Add data attributes for JS
        $data_attrs = '';
        if ($args['bale_login_enabled']) {
            $data_attrs .= ' data-bale-mode="' . esc_attr($args['bale_login_mode']) . '"';
        }
        // END: Add data attributes for JS
        
        ob_start();
        ?>
        <div class="slr-form-fields-container" <?php echo $data_attrs; ?>>
            <div class="slr-form-row slr-identifier-row">
                <?php if ($args['show_labels']): ?>
                    <label for="slr_identifier_<?php echo esc_attr($args['form_id']); ?>">ایمیل یا شماره تلفن همراه</label>
                <?php endif; ?>
                <input type="text" name="slr_identifier" class="slr-input slr-identifier-input" placeholder="مثال: 09123456789 یا user@example.com" />
            </div>

            <div class="slr-form-row slr-send-otp-row">
                <button type="button" class="slr-button slr-send-otp-button">
                    <?php echo esc_html($args['button_texts']['send_otp']); ?>
                </button>
            </div>

            <div class="slr-form-row slr-otp-row" style="display: none;">
                <?php if ($args['show_labels']): ?>
                    <label for="slr_otp_code_<?php echo esc_attr($args['form_id']); ?>">کد یکبار مصرف</label>
                <?php endif; ?>
                <input type="text" name="slr_otp_code" class="slr-input slr-otp-input" placeholder="کد تایید" autocomplete="off" />
                
                <div class="slr-otp-actions">
                    <div class="slr-timer">ارسال مجدد تا <span class="slr-countdown">60</span> ثانیه دیگر</div>
                    <a href="#" class="slr-resend-otp-button" style="display:none;">ارسال مجدد کد</a>
                    <a href="#" class="slr-back-button">ویرایش شماره / ایمیل</a>
                </div>
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
                    <?php echo esc_html($args['button_texts']['submit']); ?>
                </button>
            </div>
            
            <?php if ($args['google_login_enabled'] || $args['telegram_login_enabled'] || $args['bale_login_enabled']): ?>
                <div class="slr-or-divider">یا</div>
            <?php endif; ?>

            <div class="slr-social-login-row">
                <?php if ($args['bale_login_enabled']): ?>
                    <a href="#" class="slr-button slr-bale-button">
                         <svg style="width: 20px; height: 20px; vertical-align: middle; margin-left: 8px;" viewBox="0 0 32 32" version="1.1" xmlns="http://www.w3.org/2000/svg"><path d="M22.645 12.934c0.552 0.956 0.276 2.19-0.681 2.742l-9.091 5.25c-0.956 0.552-2.19 0.276-2.742-0.681l-3.321-5.75c-0.552-0.956-0.276-2.19 0.681-2.742l9.091-5.25c0.956-0.552 2.19-0.276 2.742 0.681l3.321 5.75zM16 3.25c-7.042 0-12.75 5.708-12.75 12.75s5.708 12.75 12.75 12.75 12.75-5.708 12.75-12.75-5.708-12.75-12.75-12.75zM16 31.5c-8.543 0-15.5-6.957-15.5-15.5s6.957-15.5 15.5-15.5 15.5 6.957 15.5 15.5-6.957 15.5-15.5 15.5z" fill="currentColor"></path></svg>
                        <span>ورود با بله</span>
                    </a>
                <?php endif; ?>
                <?php if ($args['telegram_login_enabled']): ?>
                    <a href="#" class="slr-button slr-telegram-button">
                        <span><?php echo esc_html__('ورود با تلگرام', 'yakutlogin'); ?></span>
                    </a>
                <?php endif; ?>
                <?php if ($args['google_login_enabled']): ?>
                    <a href="<?php echo esc_url($args['google_login_url']); ?>" class="slr-button slr-google-button">
                        <i class="fab fa-google"></i> <span><?php echo esc_html($args['button_texts']['google']); ?></span>
                    </a>
                <?php endif; ?>
                <?php if ($args['discord_login_enabled']): 
                    $discord_nonce_url = wp_nonce_url(
                        add_query_arg('slr_action', 'discord_login_init', home_url('/')),
                        'slr_discord_login_init_nonce',
                        'slr_discord_nonce'
                    );
                ?>
                    <a href="<?php echo esc_url($discord_nonce_url); ?>" class="slr-button slr-discord-button">
                        <svg style="width: 20px; height: 20px; vertical-align: middle; margin-left: 8px;" role="img" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><title>Discord</title><path d="M20.317 4.3698a19.7913 19.7913 0 0 0-4.885-1.5152.0741.0741 0 0 0-.0785.0371c-.211.3753-.4447.8648-.6083 1.2495-1.8447-.276-3.68-.276-5.4868 0-.1636-.3846-.3973-.8742-.6082-1.2495a.0741.0741 0 0 0-.0785-.0371 19.7913 19.7913 0 0 0-4.885 1.5152.069.069 0 0 0-.032.0235c-1.7344 3.47-2.2764 7.033-2.068 10.6337.0185.3246.224.538.5169.538.2928 0 .4983-.2133.5168-.538a16.472 16.472 0 0 1 .4448-3.3765c.1954-.7843.43-1.5606.726-2.3215.06-.1635.14-.3246.24-.4671a.069.069 0 0 1 .0515-.0185.069.069 0 0 1 .046.032c.1636.2478.3326.504.4833.7416.4833.7843.8993 1.5606 1.2495 2.3486.2338.5463.454 1.101.6496 1.6558a.0741.0741 0 0 0 .0416.0416c.3973.1388.8132.2592 1.2292.3615.1954.0463.3907.0833.5944.1203a.0741.0741 0 0 0 .0833-.0416c.3973-.722.768-1.4625 1.101-2.2202.0416-.0925.0925-.1943.1481-.2868a.069.069 0 0 1 .0515-.032.069.069 0 0 1 .06.0235c.4833.5642.9248 1.137 1.3092 1.7115.1743.2592.3428.5184.5023.7776a.0741.0741 0 0 0 .0833.0416c.416-.1022.8319-.2226 1.2292-.3615a.0741.0741 0 0 0 .0416-.0416c.1956-.5548.416-1.1095.6496-1.6558.3514-.7879.768-1.5645 1.2495-2.3486.1481-.2375.32-.4937.4833-.7416a.069.069 0 0 1 .046-.032.069.069 0 0 1 .0515.0185c.1085.1425.1873.3036.2483.467.296.761.5323 1.537.7278 2.3216a16.472 16.472 0 0 1 .4448 3.3765c.0185.3246.224.538.5168.538.293 0 .4984-.2134.517-.538.2084-3.5927-.334-7.1637-2.068-10.6337a.069.069 0 0 0-.032-.0235zM8.02 15.3312c-1.1825 0-2.15-1.0855-2.15-2.422s.9675-2.422 2.15-2.422 2.15 1.0855 2.15 2.422-.9675 2.422-2.15 2.422zm7.965 0c-1.1825 0-2.15-1.0855-2.15-2.422s.9675-2.422 2.15-2.422 2.15 1.0855 2.15 2.422-.9675 2.422-2.15 2.422z" fill="currentColor"/></svg>
                        <span>ورود با دیسکورد</span>
                    </a>
                <?php endif; ?>
                <?php if ($args['linkedin_login_enabled']): 
                    $linkedin_nonce_url = wp_nonce_url(
                        add_query_arg('slr_action', 'linkedin_login_init', home_url('/')),
                        'slr_linkedin_login_init_nonce',
                        'slr_linkedin_nonce'
                    );
                ?>
                    <a href="<?php echo esc_url($linkedin_nonce_url); ?>" class="slr-button slr-linkedin-button">
                        <svg style="width: 16px; height: 16px; vertical-align: middle; margin-left: 8px;" viewBox="0 0 24 24" version="1.1" xmlns="http://www.w3.org/2000/svg"><path d="M20,2H4C2.9,2,2,2.9,2,4v16c0,1.1,0.9,2,2,2h16c1.1,0,2-0.9,2-2V4C22,2.9,21.1,2,20,2z M8,19H5V8h3V19z M6.5,6.7 C5.5,6.7,4.7,5.9,4.7,5s0.8-1.7,1.8-1.7s1.8,0.8,1.8,1.7S7.5,6.7,6.5,6.7z M19,19h-3v-5.6c0-1.3-0-3-1.8-3c-1.8,0-2.1,1.4-2.1,2.9 V19h-3V8h3v1.3h0c0.4-0.8,1.4-1.6,2.9-1.6c3.1,0,3.7,2,3.7,4.7V19z" fill="currentColor"/></svg>
                        <span>ورود با لینکدین</span>
                    </a>
                <?php endif; ?>
                <?php if ($args['github_login_enabled']): 
                    $github_nonce_url = wp_nonce_url(
                        add_query_arg('slr_action', 'github_login_init', home_url('/')),
                        'slr_github_login_init_nonce',
                        'slr_github_nonce'
                    );
                ?>
                    <a href="<?php echo esc_url($github_nonce_url); ?>" class="slr-button slr-github-button">
                        <svg style="width: 16px; height: 16px; vertical-align: middle; margin-left: 8px;" role="img" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><title>GitHub</title><path d="M12 .297c-6.63 0-12 5.373-12 12 0 5.303 3.438 9.8 8.205 11.385.6.113.82-.258.82-.577 0-.285-.01-1.04-.015-2.04-3.338.724-4.042-1.61-4.042-1.61C4.422 18.07 3.633 17.7 3.633 17.7c-1.087-.744.084-.729.084-.729 1.205.084 1.838 1.236 1.838 1.236 1.07 1.835 2.809 1.305 3.495.998.108-.776.417-1.305.76-1.605-2.665-.3-5.466-1.332-5.466-5.93 0-1.31.465-2.38 1.235-3.22-.135-.303-.54-1.523.105-3.176 0 0 1.005-.322 3.3 1.23.96-.267 1.98-.399 3-.405 1.02.006 2.04.138 3 .405 2.28-1.552 3.285-1.23 3.285-1.23.645 1.653.24 2.873.12 3.176.765.84 1.23 1.91 1.23 3.22 0 4.61-2.805 5.625-5.475 5.92.42.36.81 1.096.81 2.22 0 1.606-.015 2.896-.015 3.286 0 .315.21.69.825.57C20.565 22.092 24 17.592 24 12.297c0-6.627-5.373-12-12-12" fill="currentColor"/></svg>
                        <span>ورود با گیت‌هاب</span>
                    </a>
                <?php endif; ?>
            </div>
        </div><?php
        return ob_get_clean();
    }
}