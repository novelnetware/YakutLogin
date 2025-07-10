<?php

/**
 * The public-facing functionality of the plugin.
 *
 * @link       https://yakut.ir/
 * @since      1.0.0
 *
 * @package    Sms_Login_Register
 * @subpackage Sms_Login_Register/public
 */

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for enqueueing
 * the public-facing stylesheet and JavaScript.
 *
 * @package    Sms_Login_Register
 * @subpackage Sms_Login_Register/public
 * @author     Yakut Co <info@yakut.ir>
 */
class Sms_Login_Register_Public {

 /**
     * The ID of this plugin.
     * @var string
     */
    private $plugin_name;

    /**
     * The version of this plugin.
     * @var string
     */
    private $version;

    /**
     * The theme manager instance.
     * @var SLR_Theme_Manager
     */
    private $theme_manager;

    /**
     * The SMS Gateway manager.
     * @var SLR_Gateway_Manager|null
     */
    private $gateway_manager;

    /**
     * The Captcha handler instance.
     * @var SLR_Captcha_Handler|null
     */
    private $captcha_handler;

    /**
     * Ensures scripts are only enqueued once.
     * @var bool
     */
    private static $scripts_enqueued = false;

    /**
     * Initialize the class and set its properties.
     *
     * @param string             $plugin_name The name of this plugin.
     * @param string             $version     The version of this plugin.
     * @param SLR_Theme_Manager  $theme_manager The theme manager instance.
     */
public function __construct($plugin_name, $version, $theme_manager) {
    $this->plugin_name = $plugin_name;
    $this->version = $version;
    $this->theme_manager = $theme_manager;

    // Instantiate handlers once to be reused
    if (class_exists('SLR_Gateway_Manager')) {
        $this->gateway_manager = new SLR_Gateway_Manager();
    }
    if (class_exists('SLR_Captcha_Handler')) {
        $this->captcha_handler = new SLR_Captcha_Handler();
    }
}

    private static $enqueued_themes = []; // Track enqueued themes

    /**
     * Enqueues scripts and styles for a specific theme if not already done.
     *
     * @since 1.5.0
     * @param string $theme_id The ID of the theme to enqueue assets for.
     */
    public function enqueue_theme_assets( $theme_id = 'default' ) {
        if ( isset( self::$enqueued_themes[ $theme_id ] ) ) {
            return; // Already enqueued
        }

        $theme = $this->theme_manager->get_theme( $theme_id );
        if ( ! $theme ) {
            $theme = $this->theme_manager->get_theme( 'default' );
            if ( ! $theme ) return; // Bail if even default is missing
        }

        // Enqueue theme style
        if ( ! empty( $theme['style'] ) && file_exists( $theme['path'] . $theme['style'] ) ) {
            wp_enqueue_style(
                $this->plugin_name . '-theme-' . $theme['id'],
                $theme['url'] . $theme['style'],
                array(),
                $this->version
            );
        }

        // Enqueue theme script if it exists
        if ( ! empty( $theme['script'] ) && file_exists( $theme['path'] . $theme['script'] ) ) {
            wp_enqueue_script(
                $this->plugin_name . '-theme-script-' . $theme['id'],
                $theme['url'] . $theme['script'],
                array( 'jquery' ),
                $this->version,
                true
            );
        }

        self::$enqueued_themes[ $theme_id ] = true;
    }

    

    /**
     * Generates the HTML for the OTP login/registration form.
     * This function acts as an orchestrator for the dynamic theme engine.
     *
     * @since 2.0.0 (Refactored for Theme Engine)
     * @param array $args Arguments to customize the form.
     * @return string HTML of the form.
     */
    public function get_otp_form_html( $args = array() ) {
        // 1. Enqueue the main plugin's scripts (like the public JS for AJAX)
        $this->maybe_enqueue_scripts();

        // 2. Define default arguments for the form
        $default_args = array(
            'form_id'      => 'slr-otp-form-' . wp_rand(100, 999),
            'context'      => 'mixed',
            'show_labels'  => true,
            'redirect_to'  => '',
            'theme'        => 'default',
            'layout'       => 'default',
            'button_texts' => array(
                'send_otp' => __( 'ارسال کد تایید', 'yakutlogin' ),
                'submit'   => __( 'ورود / عضویت', 'yakutlogin' ),
                'google'   => __( 'ورود با گوگل', 'yakutlogin' ),
            ),
        );

        // 3. Merge user-provided args with defaults
        $args = wp_parse_args( $args, $default_args );
        if (!is_array($args['button_texts'])) {
            $args['button_texts'] = $default_args['button_texts'];
        } else {
            $args['button_texts'] = wp_parse_args($args['button_texts'], $default_args['button_texts']);
        }
        
        // 4. Get the selected theme object from the Theme Manager
        $theme = $this->theme_manager->get_theme( $args['theme'] );
        if ( ! $theme ) {
            return '<p style="color: red;">خطا: پوسته انتخاب شده (' . esc_html($args['theme']) . ') یافت نشد.</p>';
        }

        // 5. Enqueue the specific assets (CSS/JS) for this theme
        $theme_data = $theme->get_theme_data();
        foreach ( $theme->get_assets() as $asset ) {
            $handle = $asset['handle'] ?? 'slr-asset-' . sanitize_key($args['theme']) . '-' . md5($asset['src']);
            $deps = $asset['dependencies'] ?? [];
            if ($asset['type'] === 'style') {
                wp_enqueue_style($handle, $theme_data['url'] . $asset['src'], $deps, $theme_data['version'] ?? $this->version);
            } elseif ($asset['type'] === 'script') {
                wp_enqueue_script($handle, $theme_data['url'] . $asset['src'], $deps, $theme_data['version'] ?? $this->version, true);
            }
        }

        // 6. Prepare common data to pass to the theme's render method
        $options = get_option('slr_plugin_options');

        // Telegram Login data
$telegram_handler = new SLR_Telegram_Handler();
$args['telegram_login_enabled'] = $telegram_handler->is_active();

// START: Add Bale Login Data
        $args['bale_login_enabled'] = !empty($options['bale_login_enabled']);
        $args['bale_login_mode'] = $options['bale_login_mode'] ?? 'smart_only';
        // END: Add Bale Login Data

        // START: Add Discord Login Data
        $args['discord_login_enabled'] = !empty($options['discord_login_enabled']);
        // END: Add Discord Login Data

        // START: Add LinkedIn Login Data
        $args['linkedin_login_enabled'] = !empty($options['linkedin_login_enabled']);


        // START: Add GitHub Login Data
        $args['github_login_enabled'] = !empty($options['github_login_enabled']);
        // END: Add GitHub Login Data

        
        // Google Login data
        $args['google_login_enabled'] = !empty($options['google_login_enabled']) && !empty($options['google_client_id']);
        $args['google_login_url'] = '';
        if ($args['google_login_enabled']) {
            $args['google_login_url'] = add_query_arg( 'slr_action', 'google_login_init', home_url( '/' ) );
            $args['google_login_url'] = wp_nonce_url( $args['google_login_url'], 'slr_google_login_init_nonce', 'slr_google_nonce' );
        }
        
        // Captcha data
        $captcha_type = $options['captcha_type'] ?? 'none';
        $captcha_site_key = '';
        if ($captcha_type === 'recaptcha_v2' && !empty($options['recaptcha_v2_site_key'])) {
            $captcha_site_key = $options['recaptcha_v2_site_key'];
        } elseif ($captcha_type === 'turnstile' && !empty($options['turnstile_site_key'])) {
            $captcha_site_key = $options['turnstile_site_key'];
        }
        $args['captcha_type'] = $captcha_type;
        $args['captcha_site_key'] = $captcha_site_key;

        // 7. Start output buffering to build the final HTML
        ob_start();

        $container_classes = [
            'slr-otp-form-container',
            'slr-theme-' . sanitize_html_class($args['theme']),
            'slr-layout-' . sanitize_html_class($args['layout']),
        ];
        ?>
        <div id="<?php echo esc_attr($args['form_id']); ?>" class="<?php echo esc_attr(implode(' ', $container_classes)); ?>">
            <form class="slr-otp-form" method="post" onsubmit="return false;">
                
                <?php
                // 8. The magic happens here: Call the theme's own get_html method
                // The theme will now decide how to render the form with the provided args.
                echo $theme->get_html($args);
                ?>

                <div class="slr-message-area"></div>
                
                <?php wp_nonce_field( 'slr_process_form_nonce', 'slr_process_form_nonce_field' ); ?>
                <input type="hidden" name="slr_redirect_to" value="<?php echo esc_url($args['redirect_to']); ?>" />
            </form>
        </div>
        <?php
        
        // 9. Return the final HTML
        return ob_get_clean();
    }

/**
     * Enqueues the CORE scripts and styles for the plugin.
     * This function is designed to run only once per page load.
     */
    public function maybe_enqueue_scripts() {
        if (self::$scripts_enqueued) {
            return;
        }

        // Enqueue the main public stylesheet
        wp_enqueue_style(
            $this->plugin_name . '-public-base',
            SLR_PLUGIN_URL . 'public/css/sms-login-register-public.css',
            [],
            $this->version
        );

        // Enqueue the main public JavaScript file
        wp_enqueue_script(
            $this->plugin_name . '-public',
            SLR_PLUGIN_URL . 'public/js/sms-login-register-public.js',
            ['jquery'],
            $this->version,
            true
        );

        // Enqueue captcha scripts if needed
        $this->enqueue_captcha_scripts_if_needed();

        // Localize script data to pass variables from PHP to JavaScript
        wp_localize_script(
            $this->plugin_name . '-public',
            'slr_public_data',
            array(
                'ajax_url'                  => admin_url('admin-ajax.php'),
                'send_otp_nonce'            => wp_create_nonce('slr_send_otp_nonce'),
                'process_form_nonce'        => wp_create_nonce('slr_process_form_nonce'),
                'telegram_request_nonce'    => wp_create_nonce('slr_telegram_request_nonce'),
                'telegram_polling_nonce'    => wp_create_nonce('slr_telegram_polling_nonce'),
                'bale_otp_nonce'            => wp_create_nonce('slr_bale_otp_nonce'),
                'bale_bot_nonce'            => wp_create_nonce('slr_bale_bot_nonce'),
                'bale_polling_nonce'        => wp_create_nonce('slr_bale_polling_nonce'),
                'get_auth_options_nonce'    => wp_create_nonce('yakutlogin_get_auth_options_nonce'),
                'verify_auth_nonce'         => wp_create_nonce('yakutlogin_verify_auth_nonce'),
                'text_sending_otp'          => __('در حال ارسال کد...', 'yakutlogin'),
                'text_processing'           => __('در حال پردازش...', 'yakutlogin'),
                'text_error'                => __('یک خطای غیرمنتظره رخ داد.', 'yakutlogin'),
            )
        );
        
        self::$scripts_enqueued = true;
    }

/**
 * Enqueues CAPTCHA scripts if needed.
 */
public function enqueue_captcha_scripts_if_needed() {
    if (!$this->captcha_handler) return;

    $options = get_option('slr_plugin_options');
    $captcha_type = $options['captcha_type'] ?? 'none';
    
    $script_handle = '';
    $script_url = '';

    if ($captcha_type === 'recaptcha_v2' && !empty($options['recaptcha_v2_site_key'])) {
        $script_handle = 'google-recaptcha';
        $script_url = 'https://www.google.com/recaptcha/api.js?render=explicit&onload=slrRenderReCaptcha';
    } elseif ($captcha_type === 'turnstile' && !empty($options['turnstile_site_key'])) {
        $script_handle = 'cloudflare-turnstile';
        $script_url = 'https://challenges.cloudflare.com/turnstile/v0/api.js?render=explicit&onload=slrRenderTurnstile';
    }

    if ($script_handle && !wp_script_is($script_handle, 'enqueued')) {
        wp_enqueue_script($script_handle, $script_url, [], null, true);
    }
}

    /**
 * Sends an OTP email to the user with proper headers.
 *
 * @param string $email_address The recipient's email address.
 * @param string $otp The one-time password.
 * @return bool True on success, false on failure.
 */
public function send_otp_email( $email_address, $otp ) {
    $all_options = get_option('slr_plugin_options');

    if ( empty( $all_options['email_otp_enabled'] ) ) {
        slr_log('ارسال ایمیل متوقف شد: قابلیت ارسال ایمیل OTP غیرفعال است.');
        return false;
    }

    $subject_template = $all_options['otp_email_subject'] ?? 'کد تایید شما';
    $body_template    = $all_options['otp_email_body'] ?? 'کد تایید شما: {otp_code}';
    
    // جایگزینی شناسه‌ها
    $subject = str_replace( '{otp_code}', $otp, $subject_template );
    $body    = str_replace( '{otp_code}', $otp, $body_template );
    $body    = str_replace( '{site_title}', get_bloginfo( 'name' ), $body );
    $body    = str_replace( '{site_url}', home_url(), $body );
    $body    = nl2br( $body );

    $site_name  = get_bloginfo('name');
    $from_email = 'noreply@' . preg_replace('#^www\.#', '', strtolower($_SERVER['SERVER_NAME']));
    
    // تعریف هدرها برای محتوای فارسی
    $headers = [
        'Content-Type: text/html; charset=UTF-8',
        "From: {$site_name} <{$from_email}>",
    ];

    // ارسال ایمیل
    $sent = wp_mail( $email_address, $subject, $body, $headers );

    if ($sent) {
        slr_log("ایمیل با موفقیت ارسال شد.");
        return true;
    } else {
        slr_log("ارسال ایمیل با شکست مواجه شد.");
        return false;
    }
}

     /**
     * AJAX handler for sending OTP.
     * (Rewritten for clarity, security, and robustness)
     *
     * @since 1.5.1
     */
    public function ajax_send_otp() {
        // ۱. بررسی امنیتی Nonce
        check_ajax_referer( 'slr_send_otp_nonce', 'security' );

        // ۲. بررسی کپچا (در صورت فعال بودن)
        $captcha_handler = new SLR_Captcha_Handler();
        $captcha_response_token = isset( $_POST['g-recaptcha-response'] ) ? $_POST['g-recaptcha-response'] : (isset($_POST['cf-turnstile-response']) ? $_POST['cf-turnstile-response'] : '');
        if ( ! $captcha_handler->verify_captcha( $captcha_response_token ) ) {
            wp_send_json_error( array( 'message' => __( 'تایید کپچا ناموفق بود. لطفا دوباره تلاش کنید.', 'yakutlogin' ) ) );
            return;
        }

        // ۳. دریافت و اعتبارسنجی ورودی
        $identifier_input = isset( $_POST['identifier'] ) ? $_POST['identifier'] : '';
        if ( empty( $identifier_input ) ) {
            wp_send_json_error( array( 'message' => __( 'لطفا ایمیل یا شماره تلفن همراه خود را وارد کنید.', 'yakutlogin' ) ) );
            return;
        }

        // ۴. تشخیص نوع ورودی (ایمیل یا تلفن)
        $identifier_data = $this->determine_identifier_type( $identifier_input );
        $identifier      = $identifier_data['value'];
        $send_method     = $identifier_data['type'];

        if ( 'invalid' === $send_method ) {
            wp_send_json_error( array( 'message' => __( 'فرمت ورودی نامعتبر است. لطفا یک ایمیل یا شماره تلفن صحیح وارد کنید.', 'yakutlogin' ) ) );
            return;
        }

        // ۵. بررسی محدودیت زمانی (Cooldown) برای جلوگیری از اسپم
        if ( Sms_Login_Register_Otp_Handler::is_on_cooldown( $identifier, 60 ) ) {
            wp_send_json_error( array( 'message' => __( 'شما به تازگی یک کد دریافت کرده‌اید. لطفا ۶۰ ثانیه صبر کنید.', 'yakutlogin' ) ) );
            return;
        }

        // ۶. تولید و ذخیره کد OTP
        $otp    = Sms_Login_Register_Otp_Handler::generate_otp();
        $stored = Sms_Login_Register_Otp_Handler::store_otp( $identifier, $otp );

        if ( ! $stored ) {
            wp_send_json_error( array( 'message' => __( 'خطا در سیستم ذخیره‌سازی کد. لطفا با پشتیبانی تماس بگیرید.', 'yakutlogin' ) ) );
            return;
        }

        // ۷. ارسال کد بر اساس نوع ورودی
        $sent_successfully = false;
        $message_on_success = '';
        $message_on_fail = '';

        if ( 'phone' === $send_method ) {
            $gateway_manager = new SLR_Gateway_Manager();
            if ( ! $gateway_manager->get_active_gateway() ) {
                Sms_Login_Register_Otp_Handler::delete_otp( $identifier ); // حذف کد ذخیره شده در صورت خطا
                wp_send_json_error( array( 'message' => __( 'سرویس پیامک در حال حاضر فعال نیست. لطفا با ایمیل امتحان کنید یا با پشتیبانی تماس بگیرید.', 'yakutlogin' ) ) );
                return;
            }
            $sent_successfully  = $gateway_manager->send_otp( $identifier, $otp );
            $message_on_success = __( 'کد تایید با موفقیت به شماره شما پیامک شد.', 'yakutlogin' );
            $message_on_fail    = __( 'در ارسال پیامک خطایی رخ داد. لطفا دوباره تلاش کنید.', 'yakutlogin' );
        } 
        elseif ( 'email' === $send_method ) {
            $options = get_option('slr_plugin_options');
            if ( !isset($options['email_otp_enabled']) || !$options['email_otp_enabled'] ) {
                Sms_Login_Register_Otp_Handler::delete_otp( $identifier ); // حذف کد ذخیره شده در صورت خطا
                wp_send_json_error( array( 'message' => __( 'ارسال کد با ایمیل غیرفعال است. لطفا با شماره تلفن امتحان کنید.', 'yakutlogin' ) ) );
                return;
            }
            $sent_successfully  = $this->send_otp_email( $identifier, $otp );
            $message_on_success = __( 'کد تایید به ایمیل شما ارسال شد.', 'yakutlogin' );
            $message_on_fail    = __( 'در ارسال ایمیل خطایی رخ داد. لطفا دوباره تلاش کنید.', 'yakutlogin' );
        }

        // ۸. ارسال پاسخ نهایی به کاربر
        if ( $sent_successfully ) {
            wp_send_json_success( array( 'message' => $message_on_success ) );
        } else {
            Sms_Login_Register_Otp_Handler::delete_otp( $identifier ); // حذف کد در صورت شکست در ارسال
            wp_send_json_error( array( 'message' => $message_on_fail ) );
        }
    }
     /**
     * AJAX handler for processing OTP login/registration.
     * (Rewritten for clarity, security, and robustness)
     *
     * @since 1.5.1
     */
    public function ajax_process_login_register_otp() {
        // ۱. بررسی امنیتی Nonce
        check_ajax_referer( 'slr_process_form_nonce', 'slr_process_form_nonce_field' );

        // ۲. بررسی کپچا
      //  $captcha_handler = new SLR_Captcha_Handler();
       // $captcha_response_token = isset( $_POST['g-recaptcha-response'] ) ? $_POST['g-recaptcha-response'] : (isset($_POST['cf-turnstile-response']) ? $_POST['cf-turnstile-response'] : '');
      //  if ( ! $captcha_handler->verify_captcha( $captcha_response_token ) ) {
      //      wp_send_json_error( array( 'message' => __( 'تایید کپچا ناموفق بود.', 'yakutlogin' ) ) );
      //      return;
      //  }

// ۳. دریافت و اعتبارسنجی ورودی‌ها
$identifier_input = isset( $_POST['slr_identifier'] ) ? $_POST['slr_identifier'] : '';
$otp_code         = isset( $_POST['slr_otp_code'] ) ? sanitize_text_field( $_POST['slr_otp_code'] ) : '';
$redirect_to      = isset( $_POST['slr_redirect_to'] ) ? esc_url_raw( $_POST['slr_redirect_to'] ) : '';

        if ( empty( $identifier_input ) || empty( $otp_code ) ) {
            wp_send_json_error( array( 'message' => __( 'لطفا تمام فیلدها را پر کنید.', 'yakutlogin' ) ) );
            return;
        }

        // ۴. تشخیص نوع ورودی
        $identifier_data = $this->determine_identifier_type( $identifier_input );
        $identifier      = $identifier_data['value'];
        $login_method    = $identifier_data['type'];

        if ( 'invalid' === $login_method ) {
            wp_send_json_error( array( 'message' => __( 'ایمیل یا شماره تلفن نامعتبر است.', 'yakutlogin' ) ) );
            return;
        }

        // ۵. بررسی صحت کد OTP
        if ( ! Sms_Login_Register_Otp_Handler::verify_otp( $identifier, $otp_code ) ) {
            wp_send_json_error( array( 'message' => __( 'کد تایید وارد شده اشتباه یا منقضی شده است.', 'yakutlogin' ) ) );
            return;
        }

        // ۶. جستجو برای کاربر موجود
        $user = null;
        if ( 'email' === $login_method ) {
            $user = get_user_by( 'email', $identifier );
        } elseif ( 'phone' === $login_method ) {
            $users = get_users( array(
                'meta_key'   => 'slr_phone_number',
                'meta_value' => $identifier,
                'number'     => 1,
                'count_total'=> false
            ) );
            if ( ! empty( $users ) ) {
                $user = $users[0];
            }
        }

        // ۷. اگر کاربر وجود داشت (ورود)
        if ( $user ) {
            wp_clear_auth_cookie();
            wp_set_current_user( $user->ID );
            wp_set_auth_cookie( $user->ID, true );
            do_action( 'wp_login', $user->user_login, $user );

            $redirect_url = ! empty( $redirect_to ) ? $redirect_to : apply_filters( 'slr_login_redirect_url_default', admin_url(), $user );
            
            wp_send_json_success( array(
                'message'      => __( 'شما با موفقیت وارد شدید. در حال انتقال...', 'yakutlogin' ),
                'redirect_url' => apply_filters( 'slr_login_redirect_url', $redirect_url, $user )
            ) );
            return;
        }

        // ۸. اگر کاربر وجود نداشت (ثبت‌نام)
        $user_id = 0;
        $random_password = wp_generate_password( 12, false );

        if ( 'email' === $login_method ) {
            $username = sanitize_user( explode( '@', $identifier )[0], true );
            $username = 'user_' . substr(md5($identifier), 0, 8); // ساخت نام کاربری یکتا و غیرقابل حدس
            $user_id  = wp_create_user( $username, $random_password, $identifier );
        } 
        elseif ( 'phone' === $login_method ) {
            $username     = 'user_' . preg_replace( '/[^0-9]/', '', $identifier );
            $temp_email   = $username . '@' . wp_parse_url( home_url(), PHP_URL_HOST ); // ایمیل موقت
            $user_id      = wp_create_user( $username, $random_password, $temp_email );
            
            if ( ! is_wp_error( $user_id ) ) {
                update_user_meta( $user_id, 'slr_phone_number', $identifier );
                update_user_meta( $user_id, 'slr_requires_email_update', true ); // برای درخواست به‌روزرسانی ایمیل در آینده
            }
        }

        if ( is_wp_error( $user_id ) ) {
            wp_send_json_error( array( 'message' => $user_id->get_error_message() ) );
            return;
        }

        // ورود کاربر جدید
        $new_user = get_user_by( 'id', $user_id );
        wp_clear_auth_cookie();
        wp_set_current_user( $user_id, $new_user->user_login );
        wp_set_auth_cookie( $user_id, true );
        do_action( 'wp_login', $new_user->user_login, $new_user );

        $redirect_url = ! empty( $redirect_to ) ? $redirect_to : apply_filters( 'slr_registration_redirect_url_default', admin_url(), $new_user );
        
        wp_send_json_success( array(
            'message'      => __( 'ثبت‌نام شما با موفقیت انجام شد. در حال انتقال...', 'yakutlogin' ),
            'redirect_url' => apply_filters( 'slr_registration_redirect_url', $redirect_url, $new_user )
        ) );
    }

    /**
     * Renders the OTP form via shortcode.
     * [slr_otp_form context="mixed" show_labels="true" redirect_to="/my-account/" layout="default" theme="default"]
     *
     * @since 1.0.1
     * @updated 1.0.4 (Phase 13: Added layout and button text attributes)
     * @param array $atts Shortcode attributes.
     * @return string HTML output of the form.
     */
    public function render_slr_otp_form_shortcode( $atts ) {
        $atts = shortcode_atts( array(
            'context'       => 'mixed',
            'show_labels'   => 'true',
            'redirect_to'   => '',
            'theme'         => 'default',
            'layout'        => 'default', // Added layout
            'text_send_otp' => '',        // Added button texts
            'text_submit'   => '',
            'text_google'   => '',
        ), $atts, 'slr_otp_form' );

        $button_texts = []; // Initialize as array
        if (!empty($atts['text_send_otp'])) $button_texts['send_otp'] = sanitize_text_field($atts['text_send_otp']);
        if (!empty($atts['text_submit'])) $button_texts['submit'] = sanitize_text_field($atts['text_submit']);
        if (!empty($atts['text_google'])) $button_texts['google'] = sanitize_text_field($atts['text_google']);
        // If array is empty after processing, default_args in get_otp_form_html will fill it

        $args = array(
            'context'     => sanitize_text_field($atts['context']),
            'show_labels' => filter_var($atts['show_labels'], FILTER_VALIDATE_BOOLEAN),
            'redirect_to' => !empty($atts['redirect_to']) ? esc_url($atts['redirect_to']) : '',
            'theme'       => sanitize_html_class($atts['theme']),
            'layout'      => sanitize_html_class($atts['layout']),
            'button_texts'=> $button_texts, // Pass the processed array
        );
        
        return $this->get_otp_form_html( $args );
    }


    /**
     * Initializes the Google OAuth flow.
     * (No changes from Phase 13 needed here)
     */
    public function init_google_login() {
        // ... (کد قبلی بدون تغییر) ...
        if ( isset( $_GET['slr_action'] ) && $_GET['slr_action'] === 'google_login_init' ) {
            if ( ! isset( $_GET['slr_google_nonce'] ) || ! wp_verify_nonce( $_GET['slr_google_nonce'], 'slr_google_login_init_nonce' ) ) {
                wp_die( __( 'Security check failed for Google login initiation.', 'sms-login-register' ) );
            }
            $options = get_option( 'slr_plugin_options' );
            if ( !isset($options['google_login_enabled']) || !$options['google_login_enabled'] || empty($options['google_client_id']) ) {
                wp_redirect( home_url() );
                exit;
            }
            $client_id = $options['google_client_id'];
            $redirect_uri = add_query_arg( 'slr_google_auth_callback', '1', home_url( '/' ) );
            $auth_url = 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query([
                'client_id'     => $client_id,
                'redirect_uri'  => $redirect_uri,
                'response_type' => 'code',
                'scope'         => 'openid email profile',
                'access_type'   => 'online',
                'state'         => wp_create_nonce('slr_google_oauth_state')
            ]);
            wp_redirect( $auth_url );
            exit;
        }
    }

    /**
     * Handles the callback from Google after authentication.
     * (No changes from Phase 13 needed here)
     */
    public function handle_google_callback() {
        // ... (کد قبلی بدون تغییر) ...
        if ( isset( $_GET['slr_google_auth_callback'] ) && $_GET['slr_google_auth_callback'] === '1' ) {
            $options = get_option( 'slr_plugin_options' );
            if ( !isset( $_GET['state'] ) || !wp_verify_nonce( $_GET['state'], 'slr_google_oauth_state' ) ) {
                wp_redirect( home_url( '/?slr_login_error=google_state_mismatch' ) );
                exit;
            }
            if ( isset( $_GET['error'] ) ) {
                wp_redirect( home_url( '/?slr_login_error=google_access_denied' ) );
                exit;
            }
            if ( !isset( $_GET['code'] ) ) {
                wp_redirect( home_url( '/?slr_login_error=google_no_code' ) );
                exit;
            }
            if ( empty($options['google_client_id']) || empty($options['google_client_secret']) ) {
                wp_redirect( home_url( '/?slr_login_error=google_not_configured' ) );
                exit;
            }
            $code = sanitize_text_field( $_GET['code'] );
            $client_id = $options['google_client_id'];
            $client_secret = $options['google_client_secret'];
            $redirect_uri = add_query_arg( 'slr_google_auth_callback', '1', home_url( '/' ) );
            $token_url = 'https://oauth2.googleapis.com/token';
            $token_params = [
                'code'          => $code,
                'client_id'     => $client_id,
                'client_secret' => $client_secret,
                'redirect_uri'  => $redirect_uri,
                'grant_type'    => 'authorization_code'
            ];
            $response = wp_remote_post( $token_url, ['body' => $token_params, 'timeout' => 20] );
            if ( is_wp_error( $response ) ) {
                wp_redirect( home_url( '/?slr_login_error=google_token_wp_error' ) );
                exit;
            }
            $response_body = wp_remote_retrieve_body( $response );
            $token_data = json_decode( $response_body, true );
            if ( !isset( $token_data['access_token'] ) ) {
                wp_redirect( home_url( '/?slr_login_error=google_token_api_error' ) );
                exit;
            }
            $access_token = $token_data['access_token'];
            $userinfo_url = 'https://www.googleapis.com/oauth2/v3/userinfo';
            $userinfo_response = wp_remote_get( $userinfo_url, [
                'headers' => ['Authorization' => 'Bearer ' . $access_token],
                'timeout' => 20
            ]);
            if ( is_wp_error( $userinfo_response ) ) {
                wp_redirect( home_url( '/?slr_login_error=google_userinfo_wp_error' ) );
                exit;
            }
            $userinfo_body = wp_remote_retrieve_body( $userinfo_response );
            $user_info = json_decode( $userinfo_body, true );
            if ( !isset( $user_info['email'] ) || !isset($user_info['sub']) ) {
                wp_redirect( home_url( '/?slr_login_error=google_userinfo_api_error' ) );
                exit;
            }
            $google_user_id = $user_info['sub'];
            $email = sanitize_email( $user_info['email'] );
            $first_name = isset( $user_info['given_name'] ) ? sanitize_text_field( $user_info['given_name'] ) : '';
            $last_name = isset( $user_info['family_name'] ) ? sanitize_text_field( $user_info['family_name'] ) : '';
            $user = get_user_by( 'email', $email );
            if ( $user ) {
                wp_set_current_user( $user->ID, $user->user_login );
                wp_set_auth_cookie( $user->ID, true );
                update_user_meta($user->ID, 'slr_google_user_id', $google_user_id);
                do_action( 'wp_login', $user->user_login, $user );
            } else {
                $username_base = !empty($first_name) ? sanitize_user($first_name, true) : sanitize_user(explode('@', $email)[0], true);
                $username = $username_base;
                $counter = 1;
                while (username_exists($username)) {
                    $username = $username_base . $counter;
                    $counter++;
                }
                $random_password = wp_generate_password( 16 );
                $user_data = array(
                    'user_login' => $username, 'user_email' => $email, 'user_pass'  => $random_password,
                    'first_name' => $first_name, 'last_name'  => $last_name, 'display_name' => trim("$first_name $last_name"),
                );
                $user_id = wp_insert_user( $user_data );
                if ( is_wp_error( $user_id ) ) {
                    wp_redirect( home_url( '/?slr_login_error=google_registration_failed' ) );
                    exit;
                }
                update_user_meta($user_id, 'slr_google_user_id', $google_user_id);
                $new_user = get_user_by('id', $user_id);
                wp_set_current_user( $user_id, $new_user->user_login );
                wp_set_auth_cookie( $user_id, true );
                do_action( 'wp_login', $new_user->user_login, $new_user );
            }
            $redirect_url = apply_filters('slr_google_login_redirect_url', home_url('/my-account/'), $user);
            wp_redirect( $redirect_url );
            exit;
        }
    }
    /**
     * AJAX handler to generate a new Telegram login request.
     * Returns data needed to render the QR code and initiate the process.
     * @since 1.3.0
     */
    public function ajax_generate_telegram_request() {
        check_ajax_referer('slr_telegram_request_nonce', 'security');

        $telegram_handler = new SLR_Telegram_Handler();
        if (!$telegram_handler->is_active()) {
            wp_send_json_error(['message' => __('Telegram login is not enabled.', 'yakutlogin')]);
        }

        $options = get_option('slr_plugin_options');
        $bot_username = $options['telegram_bot_username'] ?? null;

        // Ensure the bot username is known before creating a link
        if (empty($bot_username)) {
            wp_send_json_error(['message' => __('Telegram bot username is not configured. Please test the connection in the admin panel first.', 'yakutlogin')]);
        }
        
        $unique_key = wp_generate_password(32, false, false);
        $session_id = wp_generate_password(32, false, false);

        set_transient('slr_tg_key_' . $unique_key, $session_id, 5 * MINUTE_IN_SECONDS);
        set_transient('slr_tg_session_' . $session_id, ['status' => 'pending'], 5 * MINUTE_IN_SECONDS);

        wp_send_json_success([
            'bot_link'   => sprintf('https://t.me/%s?start=%s', $bot_username, $unique_key),
            'unique_key' => $unique_key,
            'session_id' => $session_id,
        ]);
    }

    /**
     * AJAX handler for long-polling to check the Telegram login status.
     * @since 1.3.0
     */
    public function ajax_check_telegram_login_status() {
        check_ajax_referer( 'slr_telegram_polling_nonce', 'security' );
        
        $session_id = isset( $_POST['session_id'] ) ? sanitize_key( $_POST['session_id'] ) : '';
        if ( empty( $session_id ) ) {
            wp_send_json_error( [ 'status' => 'error', 'message' => 'Invalid session.' ] );
        }

        $session_data = get_transient( 'slr_tg_session_' . $session_id );

        if ( false === $session_data ) {
            wp_send_json_success( [ 'status' => 'expired' ] );
        }

        if ( 'success' === $session_data['status'] && ! empty( $session_data['user_id'] ) ) {
            $user_id = $session_data['user_id'];
            $user = get_user_by( 'id', $user_id );

            if ( $user ) {
                // Log the user in
                wp_clear_auth_cookie();
                wp_set_current_user( $user->ID, $user->user_login );
                wp_set_auth_cookie( $user->ID, true );
                do_action( 'wp_login', $user->user_login, $user );

                // Clean up the transient
                delete_transient( 'slr_tg_session_' . $session_id );

                // Get redirect URL
                $redirect_url = apply_filters( 'slr_login_redirect_url_default', admin_url(), $user );

                wp_send_json_success( [
                    'status'       => 'success',
                    'redirect_url' => apply_filters( 'slr_login_redirect_url', $redirect_url, $user ),
                ] );
            }
        }
        
        // Default response if status is not 'success' or 'expired'
        wp_send_json_success( [ 'status' => $session_data['status'] ?? 'pending' ] );
    }
    /**
     * AJAX handler to send an OTP via the Bale OTP service.
     */
    public function ajax_send_bale_otp() {
        check_ajax_referer('slr_bale_otp_nonce', 'security');

        $phone_number = isset($_POST['phone_number']) ? sanitize_text_field($_POST['phone_number']) : '';
        if (empty($phone_number)) {
            wp_send_json_error(['message' => 'لطفا شماره تلفن را وارد کنید.']);
        }
        
        // Normalize the number to international format for storing in transient
        $normalized_phone = (new SLR_Gateway_Manager())->normalize_iranian_phone($phone_number);
        if (!$normalized_phone) {
             wp_send_json_error(['message' => 'فرمت شماره تلفن نامعتبر است.']);
        }
        
        $otp_handler = new SLR_Bale_Otp_Handler();
        $otp_code = Sms_Login_Register_Otp_Handler::generate_otp();

        // Store OTP before sending
        Sms_Login_Register_Otp_Handler::store_otp($normalized_phone, $otp_code);

        if ($otp_handler->send_otp($normalized_phone, $otp_code)) {
            wp_send_json_success(['message' => 'کد تایید با موفقیت به اپلیکیشن بله شما ارسال شد.']);
        } else {
            // If sending failed, delete the stored OTP
            Sms_Login_Register_Otp_Handler::delete_otp($normalized_phone);
            wp_send_json_error(['message' => 'در ارسال کد تایید از طریق بله خطایی رخ داد.']);
        }
    }

    /**
     * AJAX handler to generate a new Bale Smart (Bot) Login request.
     */
    public function ajax_generate_bale_bot_request() {
        check_ajax_referer('slr_bale_bot_nonce', 'security');

        $unique_code = Sms_Login_Register_Otp_Handler::generate_otp(6); // A 6-digit code
        $session_id = wp_generate_password(32, false, false);

        set_transient('slr_bale_code_' . $unique_code, $session_id, 5 * MINUTE_IN_SECONDS);
        set_transient('slr_bale_session_' . $session_id, ['status' => 'pending'], 5 * MINUTE_IN_SECONDS);

        wp_send_json_success([
            'unique_code' => $unique_code,
            'session_id'  => $session_id,
        ]);
    }

    /**
     * AJAX handler for polling Bale Smart (Bot) Login status.
     */
    public function ajax_check_bale_login_status() {
        check_ajax_referer('slr_bale_polling_nonce', 'security');
        
        $session_id = isset($_POST['session_id']) ? sanitize_key($_POST['session_id']) : '';
        if (empty($session_id)) {
            wp_send_json_error(['status' => 'error', 'message' => 'Invalid session.']);
        }

        $session_data = get_transient('slr_bale_session_' . $session_id);

        if (false === $session_data) {
            wp_send_json_success(['status' => 'expired']);
            return;
        }

        if ('success' === ($session_data['status'] ?? 'pending') && !empty($session_data['user_id'])) {
            $user = get_user_by('id', $session_data['user_id']);
            if ($user) {
                wp_clear_auth_cookie();
                wp_set_current_user($user->ID, $user->user_login);
                wp_set_auth_cookie($user->ID, true);
                do_action('wp_login', $user->user_login, $user);
                delete_transient('slr_bale_session_' . $session_id);
                
                $redirect_url = apply_filters('slr_login_redirect_url_default', admin_url(), $user);
                wp_send_json_success([
                    'status'       => 'success',
                    'redirect_url' => apply_filters('slr_login_redirect_url', $redirect_url, $user),
                ]);
            }
        }
        
        wp_send_json_success(['status' => $session_data['status'] ?? 'pending']);
    }

    /**
     * Determines if an input is an email or a phone number.
     * Made public to be accessible from the REST API class.
     *
     * @param string $input The user input.
     * @return array Containing 'type' (email, phone, invalid) and 'value' (sanitized/normalized).
     */
public function determine_identifier_type($input) {
    $sanitized_input = sanitize_text_field(trim($input));
    if (is_email($sanitized_input)) {
        return ['type' => 'email', 'value' => $sanitized_input];
    }
    $normalized_phone = (new SLR_Gateway_Manager())->normalize_iranian_phone($sanitized_input);
    if ($normalized_phone) {
        return ['type' => 'phone', 'value' => $normalized_phone];
    }
    return ['type' => 'invalid', 'value' => null];
}

    /**
     * Finds a user by identifier (email/phone). If not found, creates a new one.
     * Made public to be accessible from the REST API class.
     *
     * @param string $identifier The normalized email or phone.
     * @param string $id_type The type of identifier ('email' or 'phone').
     * @return WP_User|WP_Error The user object on success, or a WP_Error object on failure.
     */
    public function find_or_create_user(string $identifier, string $id_type) {
        $user = null;
        if ($id_type === 'email') {
            $user = get_user_by('email', $identifier);
        } elseif ($id_type === 'phone') {
            $users = get_users(['meta_key' => 'slr_phone_number', 'meta_value' => $identifier, 'number' => 1]);
            if (!empty($users)) {
                $user = $users[0];
            }
        }

        if ($user) {
            return $user; // User found, return it.
        }

        // User not found, create a new one.
        $random_password = wp_generate_password(16, false);
        $user_id = 0;

        if ($id_type === 'email') {
            $username = 'user_' . substr(md5($identifier), 0, 8);
            $user_id = wp_create_user($username, $random_password, $identifier);
        } elseif ($id_type === 'phone') {
            $username = 'user_' . preg_replace('/[^0-9]/', '', $identifier);
            $temp_email = $username . '@' . wp_parse_url(home_url(), PHP_URL_HOST);
            $user_id = wp_create_user($username, $random_password, $temp_email);
            if (!is_wp_error($user_id)) {
                update_user_meta($user_id, 'slr_phone_number', $identifier);
            }
        }

        if (is_wp_error($user_id)) {
            return new WP_Error('user_creation_failed', $user_id->get_error_message(), ['status' => 500]);
        }
        
        return get_user_by('id', $user_id);
    }
    public function init_discord_login() {
        if (!isset($_GET['slr_action']) || $_GET['slr_action'] !== 'discord_login_init') return;
        
        if (!isset($_GET['slr_discord_nonce']) || !wp_verify_nonce($_GET['slr_discord_nonce'], 'slr_discord_login_init_nonce')) {
            wp_die('Security check failed.');
        }
        
        $options = get_option('slr_plugin_options');
        if (empty($options['discord_login_enabled']) || empty($options['discord_client_id'])) {
            wp_redirect(home_url());
            exit;
        }

        $client_id = $options['discord_client_id'];
        $redirect_uri = add_query_arg('slr_discord_auth_callback', '1', home_url('/'));
        $state = wp_create_nonce('slr_discord_oauth_state');

        $auth_url = 'https://discord.com/api/oauth2/authorize?' . http_build_query([
            'client_id'     => $client_id,
            'redirect_uri'  => $redirect_uri,
            'response_type' => 'code',
            'scope'         => 'identify email',
            'state'         => $state,
        ]);

        wp_redirect($auth_url);
        exit;
    }

public function handle_discord_callback() {
    if (!isset($_GET['slr_discord_auth_callback'])) return;

    $options = get_option('slr_plugin_options');
    if (empty($options['discord_login_enabled']) || empty($options['discord_client_id']) || empty($options['discord_client_secret'])) {
        return;
    }

    // بررسی state برای جلوگیری از حملات CSRF
    if (empty($_GET['state']) || !wp_verify_nonce($_GET['state'], 'slr_discord_oauth_state')) {
        wp_redirect(home_url('/?slr_login_error=discord_state_mismatch'));
        exit;
    }

    // بررسی وجود خطا یا کد در پاسخ دیسکورد
    if (isset($_GET['error']) || empty($_GET['code'])) {
        wp_redirect(home_url('/?slr_login_error=discord_access_denied'));
        exit;
    }

    // --- START: MODIFICATIONS (شروع اصلاحات) ---
    
    // 1. آدرس API دیسکورد برای گرفتن توکن
    $token_url = 'https://discord.com/api/v10/oauth2/token';

    // 2. ساختار صحیح درخواست به دیسکورد
    $token_response = wp_remote_post($token_url, [
        'headers' => [
            'Content-Type' => 'application/x-www-form-urlencoded',
        ],
        'body' => [
            'client_id'     => $options['discord_client_id'],
            'client_secret' => $options['discord_client_secret'],
            'grant_type'    => 'authorization_code',
            'code'          => sanitize_text_field($_GET['code']),
            'redirect_uri'  => add_query_arg('slr_discord_auth_callback', '1', home_url('/')),
            'scope'         => 'identify email',
        ]
    ]);

    $response_body = wp_remote_retrieve_body($token_response);
    $response_code = wp_remote_retrieve_response_code($token_response);
    $token_data = json_decode($response_body, true);

    // بررسی اینکه آیا access_token دریافت شده است یا نه
    if (is_wp_error($token_response) || empty($token_data['access_token'])) {
        // لاگ کردن خطا برای دیباگ در آینده
        error_log('--- YakutLogin Discord Token Error ---');
        error_log('Response Code: ' . $response_code);
        error_log('Response Body: ' . $response_body);
        if (is_wp_error($token_response)) {
            error_log('WP_Error: ' . $token_response->get_error_message());
        }
        error_log('------------------------------------');
        
        wp_redirect(home_url('/?slr_login_error=discord_token_error'));
        exit;
    }
    // --- END: MODIFICATIONS (پایان اصلاحات) ---

    // دریافت اطلاعات کاربر از دیسکورد با استفاده از توکن
    $user_response = wp_remote_get('https://discord.com/api/v10/users/@me', [
        'headers' => ['Authorization' => 'Bearer ' . $token_data['access_token']]
    ]);

    $user_info = json_decode(wp_remote_retrieve_body($user_response), true);

    if (empty($user_info['email'])) {
        wp_redirect(home_url('/?slr_login_error=discord_no_email'));
        exit;
    }

    // یافتن یا ساخت کاربر در وردپرس
    $user = $this->find_or_create_user($user_info['email'], 'email');
    if (is_wp_error($user)) {
        wp_redirect(home_url('/?slr_login_error=user_creation_failed'));
        exit;
    }
    
    // لاگین کردن کاربر و ریدایرکت
    wp_set_current_user($user->ID, $user->user_login);
    wp_set_auth_cookie($user->ID, true);
    do_action('wp_login', $user->user_login, $user);

    wp_redirect(apply_filters('slr_social_login_redirect_url', home_url('/'), $user, 'discord'));
    exit;
}

    public function init_linkedin_login() {
        if (!isset($_GET['slr_action']) || $_GET['slr_action'] !== 'linkedin_login_init') return;

        if (!isset($_GET['slr_linkedin_nonce']) || !wp_verify_nonce($_GET['slr_linkedin_nonce'], 'slr_linkedin_login_init_nonce')) {
            wp_die('Security check failed.');
        }
        
        $options = get_option('slr_plugin_options');
        if (empty($options['linkedin_login_enabled']) || empty($options['linkedin_client_id'])) {
            wp_redirect(home_url());
            exit;
        }

        $state = wp_create_nonce('slr_linkedin_oauth_state');
        set_transient('slr_linkedin_state_' . $state, 'valid', 5 * MINUTE_IN_SECONDS);

        $auth_url = 'https://www.linkedin.com/oauth/v2/authorization?' . http_build_query([
            'response_type' => 'code',
            'client_id'     => $options['linkedin_client_id'],
            'redirect_uri'  => add_query_arg('slr_linkedin_auth_callback', '1', home_url('/')),
            'state'         => $state,
            'scope'         => 'r_liteprofile r_emailaddress', // Scopes to get basic profile and email
        ]);

        wp_redirect($auth_url);
        exit;
    }

    public function handle_linkedin_callback() {
        if (!isset($_GET['slr_linkedin_auth_callback'])) return;

        $options = get_option('slr_plugin_options');
        if (empty($options['linkedin_login_enabled'])) return;
        
        if (empty($_GET['state']) || !get_transient('slr_linkedin_state_' . $_GET['state'])) {
            wp_redirect(home_url('/?slr_login_error=linkedin_state_mismatch'));
            exit;
        }
        delete_transient('slr_linkedin_state_' . $_GET['state']);

        if (empty($_GET['code'])) {
            wp_redirect(home_url('/?slr_login_error=linkedin_no_code'));
            exit;
        }

        // Exchange code for access token
        $token_response = wp_remote_post('https://www.linkedin.com/oauth/v2/accessToken', [
            'body' => [
                'grant_type'    => 'authorization_code',
                'code'          => sanitize_text_field($_GET['code']),
                'redirect_uri'  => add_query_arg('slr_linkedin_auth_callback', '1', home_url('/')),
                'client_id'     => $options['linkedin_client_id'],
                'client_secret' => $options['linkedin_client_secret'],
            ]
        ]);
        
        $token_data = json_decode(wp_remote_retrieve_body($token_response), true);
        if (empty($token_data['access_token'])) {
            wp_redirect(home_url('/?slr_login_error=linkedin_token_error'));
            exit;
        }

        // Get user's primary email
        $email_response = wp_remote_get('https://api.linkedin.com/v2/emailAddress?q=members&projection=(elements*(handle~))', [
            'headers' => ['Authorization' => 'Bearer ' . $token_data['access_token']]
        ]);
        $email_data = json_decode(wp_remote_retrieve_body($email_response), true);
        $email = $email_data['elements'][0]['handle~']['emailAddress'] ?? null;

        if (empty($email)) {
            wp_redirect(home_url('/?slr_login_error=linkedin_no_email'));
            exit;
        }

        // Find or create the WordPress user
        $user = $this->find_or_create_user($email, 'email');
        if (is_wp_error($user)) {
            wp_redirect(home_url('/?slr_login_error=user_creation_failed'));
            exit;
        }
        
        // Log the user in and redirect
        wp_set_current_user($user->ID, $user->user_login);
        wp_set_auth_cookie($user->ID, true);
        do_action('wp_login', $user->user_login, $user);

        wp_redirect(apply_filters('slr_social_login_redirect_url', home_url('/'), $user, 'linkedin'));
        exit;
    }

    public function init_github_login() {
        if (!isset($_GET['slr_action']) || $_GET['slr_action'] !== 'github_login_init') return;
        if (!isset($_GET['slr_github_nonce']) || !wp_verify_nonce($_GET['slr_github_nonce'], 'slr_github_login_init_nonce')) {
            wp_die('Security check failed.');
        }
        
        $options = get_option('slr_plugin_options');
        if (empty($options['github_login_enabled']) || empty($options['github_client_id'])) {
            wp_redirect(home_url());
            exit;
        }

        $state = wp_create_nonce('slr_github_oauth_state');
        set_transient('slr_github_state_' . $state, 'valid', 5 * MINUTE_IN_SECONDS);

        $auth_url = 'https://github.com/login/oauth/authorize?' . http_build_query([
            'client_id'    => $options['github_client_id'],
            'redirect_uri' => add_query_arg('slr_github_auth_callback', '1', home_url('/')),
            'scope'        => 'read:user user:email',
            'state'        => $state,
        ]);

        wp_redirect($auth_url);
        exit;
    }

    public function handle_github_callback() {
        if (!isset($_GET['slr_github_auth_callback'])) return;

        $options = get_option('slr_plugin_options');
        if (empty($options['github_login_enabled'])) return;

        if (empty($_GET['state']) || !get_transient('slr_github_state_' . $_GET['state'])) {
            wp_redirect(home_url('/?slr_login_error=github_state_mismatch'));
            exit;
        }
        delete_transient('slr_github_state_' . $_GET['state']);

        if (empty($_GET['code'])) {
            wp_redirect(home_url('/?slr_login_error=github_no_code'));
            exit;
        }

        // Exchange code for access token
        $token_response = wp_remote_post('https://github.com/login/oauth/access_token', [
            'headers' => ['Accept' => 'application/json'],
            'body'    => [
                'client_id'     => $options['github_client_id'],
                'client_secret' => $options['github_client_secret'],
                'code'          => sanitize_text_field($_GET['code']),
            ]
        ]);
        
        $token_data = json_decode(wp_remote_retrieve_body($token_response), true);
        if (empty($token_data['access_token'])) {
            wp_redirect(home_url('/?slr_login_error=github_token_error'));
            exit;
        }

        // Get user's primary, verified email
        $email_response = wp_remote_get('https://api.github.com/user/emails', [
            'headers' => ['Authorization' => 'Bearer ' . $token_data['access_token']]
        ]);
        $emails = json_decode(wp_remote_retrieve_body($email_response), true);
        $primary_email = null;
        if (is_array($emails)) {
            foreach ($emails as $email_item) {
                if ($email_item['primary'] && $email_item['verified']) {
                    $primary_email = $email_item['email'];
                    break;
                }
            }
        }

        if (!$primary_email) {
            wp_redirect(home_url('/?slr_login_error=github_no_verified_email'));
            exit;
        }

        // Find or create the WordPress user
        $user = $this->find_or_create_user($primary_email, 'email');
        if (is_wp_error($user)) {
            wp_redirect(home_url('/?slr_login_error=user_creation_failed'));
            exit;
        }

        // Log the user in and redirect
        wp_set_current_user($user->ID, $user->user_login);
        wp_set_auth_cookie($user->ID, true);
        do_action('wp_login', $user->user_login, $user);

        wp_redirect(apply_filters('slr_social_login_redirect_url', home_url('/'), $user, 'github'));
        exit;
    }

// این متد جدید را به کلاس اضافه کنید
    public function handle_wp_mail_failed( $wp_error ) {
        slr_log('--- خطای بحرانی WP_MAIL رخ داد ---');
        slr_log($wp_error);
        slr_log('--- پایان خطای WP_MAIL ---');
    }
}