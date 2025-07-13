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
    
    if ( !empty($controls_file) && file_exists($controls_file) ) {
      require $controls_file;
    }
  }

  /**
  * **نسخه نهایی و اصلاح شده**
     * این تابع، ساختار HTML فرم را تولید می‌کند.
  * @param array $args داده‌های مورد نیاز از جمله لینک‌ها، کلیدها و متن‌ها.
  * @return string کد HTML نهایی فرم.
  */
  public function get_html(array $args): string {
    $data_attrs = '';
    if ($args['bale_login_enabled']) {
      $data_attrs .= ' data-bale-mode="' . esc_attr($args['bale_login_mode']) . '"';
    }
    
    ob_start();
    ?>
    <?php  ?>
<?php if ( ! empty( $args['logo_url'] ) ) : ?>
    <div class="slr-form-logo">
        <img src="<?php echo esc_url( $args['logo_url'] ); ?>" alt="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?>">
    </div>
<?php endif; ?>
<?php  ?>
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

        <div class="slr-otp-inputs" dir="ltr">
          <input type="tel" class="slr-otp-digit" maxlength="1" data-index="0" />
          <input type="tel" class="slr-otp-digit" maxlength="1" data-index="1" />
          <input type="tel" class="slr-otp-digit" maxlength="1" data-index="2" />
          <input type="tel" class="slr-otp-digit" maxlength="1" data-index="3" />
          <input type="tel" class="slr-otp-digit" maxlength="1" data-index="4" />
          <input type="tel" class="slr-otp-digit" maxlength="1" data-index="5" />
        </div>
        <input type="hidden" name="slr_otp_code" class="slr-otp-input-hidden" />
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
      
            <?php
            // بررسی می‌کنیم آیا حداقل یکی از روش‌های ورود اجتماعی فعال است یا خیر
            $is_any_social_enabled = $args['google_login_enabled'] || $args['telegram_login_enabled'] || $args['bale_login_enabled'] || $args['discord_login_enabled'] || $args['linkedin_login_enabled'] || $args['github_login_enabled'];
            ?>
      <?php if ($is_any_social_enabled): ?>
        
      <?php endif; ?>

      <div class="slr-social-login-row">
        <?php 
        $is_any_social_enabled = $args['google_login_enabled'] || $args['bale_login_enabled'] || $args['discord_login_enabled'] || $args['linkedin_login_enabled'] || $args['github_login_enabled'];
?>
<?php if ($is_any_social_enabled): ?>
    <div class="slr-or-divider"><span>یا</span></div>
    
    <ul class="slr-social-icons-wrapper">
        <?php 
        $social_providers = [
            'google' => [
                'enabled' => $args['google_login_enabled'],
                'url'     => $args['google_login_url'] ?? '#',
                'tooltip' => $args['button_texts']['google'] ?? 'ورود با گوگل',
                'icon'    => $args['icons']['google'] ?? 'fab fa-google'
            ],
            // سایر سرویس‌ها را به همین شکل اضافه کنید...
            'github' => [
                'enabled' => $args['github_login_enabled'],
                'url'     => wp_nonce_url(add_query_arg('slr_action', 'github_login_init', home_url('/')), 'slr_github_login_init_nonce', 'slr_github_nonce'),
                'tooltip' => $args['button_texts']['github'] ?? 'ورود با گیت‌هاب',
                'icon'    => $args['icons']['github'] ?? 'fab fa-github'
            ],
             'bale' => [
                'enabled' => $args['bale_login_enabled'],
                'url'     => '#', // آدرس URL ندارد، با جاوااسکریپت مدیریت می‌شود
                'tooltip' => 'ورود با بله',
                'icon'    => $args['icons']['bale'] ?? 'fa-bold' // یک آیکون پیش‌فرض
            ],
        ];

        foreach($social_providers as $provider_id => $provider):
            if ($provider['enabled']): ?>
                <li class="icon <?php echo esc_attr($provider_id); ?>" data-provider="<?php echo esc_attr($provider_id); ?>" data-url="<?php echo esc_url($provider['url']); ?>">
                    <span class="tooltip"><?php echo esc_html($provider['tooltip']); ?></span>
                    <span><i class="<?php echo esc_attr($provider['icon']); ?>"></i></span>
                </li>
            <?php endif;
        endforeach;
        ?>
    </ul>
<?php endif; ?>
      </div>
    </div>
    
     <?php // کد زیر را به انتهای متد اضافه کنید ?>
    
    <?php
    return ob_get_clean();
  }
}