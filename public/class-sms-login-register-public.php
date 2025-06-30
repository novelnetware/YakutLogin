<?php

/**
 * The public-facing functionality of the plugin.
 *
 * @link       https://example.com/
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
 * @author     Your Name <email@example.com>
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
     *
     * @since 1.0.1
     * @updated 1.0.4 (Phase 13: Added layout and button_texts arguments)
     * @param array $args Arguments to customize the form.
     * @return string HTML of the form.
     */
    public function get_otp_form_html( $args = array() ) {
        $this->maybe_enqueue_scripts(); // Ensure scripts & styles are enqueued

        $default_args = array(
            'form_id'     => 'slr-otp-form-' . wp_rand(100, 999),
            'context'     => 'mixed',
            'show_labels' => true,
            'redirect_to' => '',
            'theme'       => 'default',
            'layout'      => 'default', // New: 'default', 'compact', 'inline_labels'
            'button_texts' => array(     // New: For button texts
                'send_otp' => __( 'ارسال کد تایید', 'yakutlogin' ),
                'submit'   => __( 'ورود / عضویت با پیامک', 'yakutlogin' ),
                'google'   => __( 'ورود توسط گوگل', 'yakutlogin' ),
            ),
        );
        $args = wp_parse_args( $args, $default_args );

        // Enqueue assets for the selected theme
        $this->enqueue_theme_assets( $args['theme'] );

        // Ensure button_texts is an array and merged with defaults
        if (!is_array($args['button_texts'])) {
            $args['button_texts'] = $default_args['button_texts'];
        } else {
            $args['button_texts'] = wp_parse_args($args['button_texts'], $default_args['button_texts']);
        }

        $form_classes = ['slr-otp-form-container'];
        if (!empty($args['theme'])) {
            $form_classes[] = 'slr-theme-' . sanitize_html_class($args['theme']);
        }
        if (!empty($args['layout'])) { // Apply layout class
            $form_classes[] = 'slr-layout-' . sanitize_html_class($args['layout']);
        }

        // Prepare options for Google Login button and CAPTCHA
        $options = get_option('slr_plugin_options');
        $google_login_enabled = isset($options['google_login_enabled']) && $options['google_login_enabled'] && !empty($options['google_client_id']);
        $google_login_url = '';
        if ($google_login_enabled) {
            $google_login_url = add_query_arg( 'slr_action', 'google_login_init', home_url( '/' ) );
            $google_login_url = wp_nonce_url( $google_login_url, 'slr_google_login_init_nonce', 'slr_google_nonce' );
        }

        $captcha_type = isset( $options['captcha_type'] ) ? $options['captcha_type'] : 'none';
        $captcha_html = '';
        if ( $captcha_type === 'recaptcha_v2' && !empty($options['recaptcha_v2_site_key']) ) {
            $captcha_html = '<div class="slr-form-row slr-captcha-row"><div class="g-recaptcha" data-sitekey="' . esc_attr( $options['recaptcha_v2_site_key'] ) . '"></div></div>';
        } elseif ( $captcha_type === 'turnstile' && !empty($options['turnstile_site_key']) ) {
            $captcha_html = '<div class="slr-form-row slr-captcha-row"><div class="cf-turnstile" data-sitekey="' . esc_attr( $options['turnstile_site_key'] ) . '"></div></div>';
        }

        ob_start();
        ?>
        <div id="<?php echo esc_attr($args['form_id']); ?>" class="<?php echo esc_attr(implode(' ', $form_classes)); ?>">
            <form class="slr-otp-form" method="post">
                <?php if ($google_login_enabled) : ?>
                    <?php endif; ?>
                
                <?php // --- Start of Unified Identifier Field --- ?>
                <div class="slr-form-row slr-identifier-row">
                    <?php if ($args['show_labels'] && $args['layout'] !== 'inline_labels'): ?>
                        <label for="slr_identifier_<?php echo esc_attr($args['form_id']); ?>"><?php _e('ایمیل یا شماره تلفن همراه', 'yakutlogin'); ?></label>
                    <?php endif; ?>
                    <input type="text" name="slr_identifier" id="slr_identifier_<?php echo esc_attr($args['form_id']); ?>" class="slr-input slr-identifier-input" placeholder="<?php echo esc_attr(__('مثال: 09123456789 یا user@example.com', 'yakutlogin')); ?>" />
                </div>
                <?php // --- End of Unified Identifier Field --- ?>

                <div class="slr-form-row slr-actions-row">
    <button type="button" class="slr-button slr-send-otp-button">
        <?php echo esc_html( $args['button_texts']['send_otp'] ); ?>
    </button>
    
    <?php
    // بررسی می‌کنیم که آیا قابلیت WebAuthn در تنظیمات فعال است یا خیر
    $options = get_option('slr_plugin_options', []);
    if (!empty($options['webauthn_enabled'])) :
    ?>
    <button type="button" id="slr-webauthn-login-button" class="slr-button slr-webauthn-button" style="display:none; margin-top: 10px;">
        <i class="fas fa-fingerprint"></i> <?php _e('ورود با اثر انگشت', 'yakutlogin'); ?>
    </button>
    <?php endif; ?>
</div>


                <div class="slr-form-row slr-send-otp-row">
                    <button type="button" class="slr-button slr-send-otp-button">
                        <?php echo esc_html( $args['button_texts']['send_otp'] ); ?>
                    </button>
                </div>

                <div class="slr-form-row slr-otp-row" style="display: none;">
                    <?php if ($args['show_labels'] && $args['layout'] !== 'inline_labels'): ?>
                        <label for="slr_otp_code_<?php echo esc_attr($args['form_id']); ?>"><?php _e('کد یکبار مصرف', 'yakutlogin'); ?></label>
                    <?php endif; ?>
                    <input type="text" name="slr_otp_code" id="slr_otp_code_<?php echo esc_attr($args['form_id']); ?>" class="slr-input slr-otp-input" placeholder="<?php echo esc_attr( ($args['show_labels'] && $args['layout'] === 'inline_labels') ? __('کد یکبار مصرف', 'yakutlogin') : __('کد تایید', 'yakutlogin') ); ?>" autocomplete="off" />
                </div>
                
                <?php echo $captcha_html; // Output CAPTCHA HTML ?>

                <div class="slr-form-row slr-submit-row" style="display: none;">
                     <button type="submit" name="slr_submit" class="slr-button slr-submit-button">
                        <?php echo esc_html( $args['button_texts']['submit'] ); ?>
                    </button>
                </div>
                <div class="slr-message-area" style="margin-top:10px;"></div>
                
                <?php wp_nonce_field( 'slr_process_form_nonce', 'slr_process_form_nonce_field' ); ?>
                <input type="hidden" name="slr_redirect_to" value="<?php echo esc_url($args['redirect_to']); ?>" />
            </form>
        </div>
        <?php
        return ob_get_clean();
    }

     /**
     * Determines if an input is an email or a phone number.
     *
     * @param string $input The user input.
     * @return array Containing 'type' (email, phone, invalid) and 'value' (sanitized/normalized).
     */
    private function determine_identifier_type( $input ) {
        $sanitized_input = sanitize_text_field( trim( $input ) );

        // Check for email
        if ( is_email( $sanitized_input ) ) {
            return [ 'type' => 'email', 'value' => $sanitized_input ];
        }

        // Check for phone number
        $gateway_manager = new SLR_Gateway_Manager();
        $normalized_phone = $gateway_manager->normalize_iranian_phone( $sanitized_input );
        if ( $normalized_phone ) {
            return [ 'type' => 'phone', 'value' => $normalized_phone ];
        }

        return [ 'type' => 'invalid', 'value' => null ];
    }

    /**
 * Enqueues base scripts and styles if not already done.
 * The theme-specific assets are now enqueued by get_otp_form_html.
 *
 * @since 1.0.1
 */
public function maybe_enqueue_scripts() {
    if (self::$scripts_enqueued) {
        return;
    }

    global $post;
    $shortcode_found = false;

    // List of shortcodes to check for.
    $shortcodes = [
        'sms_login_register_form',
        'slr_otp_form',
        'custom_login_form'
    ];

    // Check if the post content contains any of the shortcodes.
    if (is_a($post, 'WP_Post')) {
        foreach($shortcodes as $shortcode) {
            if (has_shortcode($post->post_content, $shortcode)) {
                $shortcode_found = true;
                break;
            }
        }
    }

    // Fallback for cases where has_shortcode might not work
    if (!$shortcode_found) {
        // Fallback logic here
    }

    // If a shortcode is found, enqueue the assets.
    if ($shortcode_found) {
        // Enqueue public-facing stylesheet.
        wp_enqueue_style(
            $this->plugin_name,
            plugin_dir_url(__FILE__) . 'css/sms-login-register-public.css',
            [],
            $this->version,
            'all'
        );

        // Enqueue the selected theme's stylesheet.
        $this->theme_manager->enqueue_selected_theme_style();

        wp_enqueue_script(
            $this->plugin_name . '-public',
            SLR_PLUGIN_URL . 'public/js/sms-login-register-public.js',
            array('jquery'),
            $this->version,
            true
        );

        wp_enqueue_style(
            $this->plugin_name . '-public',
            SLR_PLUGIN_URL . 'public/css/sms-login-register-public.css',
            array(),
            $this->version
        );

        $this->enqueue_captcha_scripts_if_needed();

        // Localize script
        wp_localize_script(
            $this->plugin_name . '-public',
            'slr_public_data',
            array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'send_otp_nonce' => wp_create_nonce('slr_send_otp_nonce'),
                'process_form_nonce' => wp_create_nonce('slr_process_form_nonce'),
                'text_sending_otp' => __('ارسال کد تایید...', 'yakutlogin'),
                'text_processing' => __('درحال پردازش...', 'yakutlogin'),
                'text_otp_sent' => __('کد تایید ارسال شد', 'yakutlogin'),
                'text_error_sending_otp' => __('خطا در ارسال کد تایید . لطفا بعدا مجدد امتحان کنید', 'yakutlogin'),
                'text_invalid_email' => __('لطفا یک ایمیل معتبر وارد کنید', 'yakutlogin'),
                'text_invalid_phone' => __('لطفا یک شماره تلفن معتبر وارد کنید', 'yakutlogin'),
                'text_fill_otp' => __('لطفا کد تایید را وارد کنید', 'yakutlogin'),
                'text_login_success' => __('با موفقیت وارد شدید', 'yakutlogin'),
                'text_registration_success' => __('ثبت نام شما با موفقیت انجام شد', 'yakutlogin'),
            )
        );
        
        self::$scripts_enqueued = true;
    } // این براکت بسته شدن if ($shortcode_found) گم شده بود
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
     * Adds OTP field and send OTP button to the login form (wp-login.php).
     * (This method and the register form one might need refactoring to use the new args of get_otp_form_html if you want full consistency)
     * @since 1.0.0
     */
    public function add_otp_fields_to_login_form() {
        $this->maybe_enqueue_scripts();
        $options = get_option( 'slr_plugin_options' );
        // Check if any OTP method (email or SMS via a configured provider) is potentially available
        $sms_gateway_manager = new SLR_Gateway_Manager();
        $sms_enabled = $sms_gateway_manager->get_active_gateway() ? true : false;
        $email_otp_enabled = isset( $options['email_otp_enabled'] ) && $options['email_otp_enabled'];

        if ( ! $email_otp_enabled && ! $sms_enabled ) {
            return;
        }
        ?>
        <div class="slr-otp-wp-login-section">
            <h4><?php _e('ورود با رمز یکبار مصرف', 'yakutlogin'); ?></h4>
            <?php if ($sms_enabled): // Add phone field for wp-login.php if SMS is possible ?>
            <p>
                <label for="slr_phone_login_wp"><?php _e('تلفن همراه', 'yakutlogin'); ?></label>
                <input type="tel" name="slr_phone_wp_login" id="slr_phone_login_wp" class="input" value="" size="20" />
            </p>
            <?php endif; ?>
            <?php if ($email_otp_enabled): // Still show email field if email OTP is on ?>
            <p class="description"><em><?php _e('نام کاربری', 'yakutlogin'); ?></em></p>
            <?php endif; ?>
            <p>
                <button type="button" id="slr-send-otp-button-login-wp" 
                        data-email-field="#user_login" 
                        data-phone-field="#slr_phone_login_wp" <?php // Added phone field data attribute ?>
                        data-message-target="#slr-message-login-wp" 
                        class="button button-secondary slr-send-otp-button-generic">
                    <?php _e( 'ارسال پیامک', 'yakutlogin' ); ?>
                </button>
            </p>
            <p>
                <label for="slr_otp_code_login_wp"><?php _e( 'کد یکبار مصرف', 'yakutlogin' ); ?></label>
                <input type="text" name="slr_otp_code" id="slr_otp_code_login_wp" class="input" value="" size="20" autocomplete="off" />
            </p>
            <div id="slr-message-login-wp" class="slr-message-area" style="margin-top:10px;"></div>
            <?php
            // CAPTCHA for wp-login.php forms
            $captcha_type = isset( $options['captcha_type'] ) ? $options['captcha_type'] : 'none';
            if ( $captcha_type === 'recaptcha_v2' && !empty($options['recaptcha_v2_site_key']) ) {
                echo '<div style="margin-bottom:10px;" class="g-recaptcha" data-sitekey="' . esc_attr( $options['recaptcha_v2_site_key'] ) . '"></div>';
            } elseif ( $captcha_type === 'turnstile' && !empty($options['turnstile_site_key']) ) {
                echo '<div style="margin-bottom:10px;" class="cf-turnstile" data-sitekey="' . esc_attr( $options['turnstile_site_key'] ) . '"></div>';
            }
            ?>
            <?php wp_nonce_field( 'slr_send_otp_nonce', 'slr_send_otp_nonce_field_login_wp' ); // Nonce for sending OTP ?>
            <?php // The main form submission for wp-login.php handles its own nonces. slr_otp_code is verified by authenticate hook. ?>
            <input type="hidden" name="slr_action" value="login_with_otp">
        </div>
        <?php
    }

    /**
     * Adds OTP field and send OTP button to the registration form (wp-login.php).
     * @since 1.0.0
     */
    public function add_otp_fields_to_register_form() {
        $this->maybe_enqueue_scripts();
        $options = get_option( 'slr_plugin_options' );
        $sms_gateway_manager = new SLR_Gateway_Manager();
        $sms_enabled = $sms_gateway_manager->get_active_gateway() ? true : false;
        $email_otp_enabled = isset( $options['email_otp_enabled'] ) && $options['email_otp_enabled'];

        if ( ! $email_otp_enabled && ! $sms_enabled ) {
            return;
        }
        ?>
        <div class="slr-otp-wp-register-section">
            <h4><?php _e('احراز توسط کد یکبار مصرف', 'yakutlogin'); ?></h4>
            <?php if ($sms_enabled): ?>
            <p>
                <label for="slr_phone_register_wp"><?php _e('تلفن همراه', 'yakutlogin'); ?></label>
                <input type="tel" name="slr_phone_wp_register" id="slr_phone_register_wp" class="input" value="" size="20" />
                 <p class="description"><em><?php _e('کد تایید به ایمیل یا شماره موبایل شما ارسال خواهد شد', 'yakutlogin'); ?></em></p>
            </p>
            <?php else: ?>
            <p class="description"><em><?php _e( 'کد تایید به ایمیل یا شماره موبایل شما ارسال خواهد شد', 'yakutlogin' ); ?></em></p>
            <?php endif; ?>
            <p>
                <button type="button" id="slr-send-otp-button-register-wp" 
                        data-email-field="#user_email" 
                        data-phone-field="#slr_phone_register_wp" <?php // Added phone field data attribute ?>
                        data-message-target="#slr-message-register-wp" 
                        class="button button-secondary slr-send-otp-button-generic">
                    <?php _e( 'ارسال کد تایید', 'yakutlogin' ); ?>
                </button>
            </p>
            <p>
                <label for="slr_otp_code_register_wp"><?php _e( 'ارسال کد تایید', 'yakutlogin' ); ?></label>
                <input type="text" name="slr_otp_code" id="slr_otp_code_register_wp" class="input" value="" size="20" autocomplete="off" />
            </p>
            <div id="slr-message-register-wp" class="slr-message-area" style="margin-top:10px;"></div>
            <?php
            // CAPTCHA for wp-login.php forms
            $captcha_type = isset( $options['captcha_type'] ) ? $options['captcha_type'] : 'none';
            if ( $captcha_type === 'recaptcha_v2' && !empty($options['recaptcha_v2_site_key']) ) {
                echo '<div style="margin-bottom:10px;" class="g-recaptcha" data-sitekey="' . esc_attr( $options['recaptcha_v2_site_key'] ) . '"></div>';
            } elseif ( $captcha_type === 'turnstile' && !empty($options['turnstile_site_key']) ) {
                echo '<div style="margin-bottom:10px;" class="cf-turnstile" data-sitekey="' . esc_attr( $options['turnstile_site_key'] ) . '"></div>';
            }
            ?>
            <?php wp_nonce_field( 'slr_send_otp_nonce', 'slr_send_otp_nonce_field_register_wp' ); ?>
            <input type="hidden" name="slr_action" value="register_with_otp">
        </div>
        <?php
    }


    /**
     * Sends an OTP email to the user.
     * (No changes from Phase 13 needed here)
     */
    public function send_otp_email( $email_address, $otp ) {
        // ... (کد قبلی بدون تغییر) ...
        $options = get_option( 'slr_plugin_options' );

        if ( ! isset( $options['email_otp_enabled'] ) || ! $options['email_otp_enabled'] ) {
            return false;
        }
        $subject_template = isset( $options['otp_email_subject'] ) ? $options['otp_email_subject'] : __( 'کد تایید شما', 'yakutlogin' );
        $body_template = isset( $options['otp_email_body'] ) ? $options['otp_email_body'] : __( "کد تایید شما : {otp_code}\nاین کد به مدت 5 دقیقه معتبر میباشد.", 'yakutlogin' );
        $subject = str_replace( '{otp_code}', $otp, $subject_template );
        $body = str_replace( '{otp_code}', $otp, $body_template );
        $body = str_replace( '{site_title}', get_bloginfo( 'name' ), $body );
        $body = str_replace( '{site_url}', home_url(), $body );
        $body = nl2br( $body );
        $headers = array('Content-Type: text/html; charset=UTF-8');
        if ( ! function_exists( 'wp_mail' ) ) {
            require_once ABSPATH . WPINC . '/pluggable.php';
        }
        return wp_mail( $email_address, $subject, $body, $headers );
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
     * Authenticates a user with OTP if provided (for wp-login.php).
     * A
     */
    public function authenticate_with_otp( $user, $username, $password ) {
        
        if ( isset( $_POST['slr_otp_code'] ) && ! empty( $_POST['slr_otp_code'] ) && isset( $_POST['slr_action'] ) && $_POST['slr_action'] === 'login_with_otp' ) {
            $submitted_otp = sanitize_text_field( $_POST['slr_otp_code'] );
            
            $identifier_from_username_field = sanitize_text_field( $username );
            $identifier_from_phone_field = isset($_POST['slr_phone_wp_login']) ? sanitize_text_field($_POST['slr_phone_wp_login']) : '';
            
            $identifier_for_otp = '';
            $gateway_manager = class_exists('SLR_Gateway_Manager') ? new SLR_Gateway_Manager() : null;

            if (!empty($identifier_from_phone_field) && $gateway_manager) {
                $normalized_phone = $gateway_manager->normalize_iranian_phone($identifier_from_phone_field);
                if ($normalized_phone) {
                    $identifier_for_otp = $normalized_phone;
                }
            }
            
            if (empty($identifier_for_otp)) { // Fallback to email/username if phone not used or invalid
                 if (is_email($identifier_from_username_field)) {
                    $identifier_for_otp = $identifier_from_username_field;
                } else {
                    $user_obj_by_login = get_user_by('login', $identifier_from_username_field);
                    if ($user_obj_by_login && !empty($user_obj_by_login->user_email)) {
                        $identifier_for_otp = $user_obj_by_login->user_email;
                    } else if (!empty($identifier_from_username_field)){ // if user entered something but it's not email/valid username
                         return new WP_Error( 'slr_otp_error', __( 'لطفا یک ایمیل معتبر وارد کنید', 'yakutlogin' ) );
                    }
                }
            }

            if ( empty( $identifier_for_otp ) ) {
                return new WP_Error( 'slr_otp_error', __( 'نام کاربری ، ایمیل یا شماره همراه شما نادرست میباشد', 'yakutlogin' ) );
            }
            if( empty( $submitted_otp ) ){
                 return new WP_Error( 'slr_otp_error', __( 'کد تایید نمیتواند خالی باشد', 'yakutlogin' ) );
            }
            
            // CAPTCHA for wp-login.php form submission
            $captcha_handler = class_exists('SLR_Captcha_Handler') ? new SLR_Captcha_Handler() : null;
            $captcha_response_token = isset( $_POST['g-recaptcha-response'] ) ? $_POST['g-recaptcha-response'] : (isset($_POST['cf-turnstile-response']) ? $_POST['cf-turnstile-response'] : '');
            if ($captcha_handler) {
                $options = get_option('slr_plugin_options');
                $captcha_type = isset( $options['captcha_type'] ) ? $options['captcha_type'] : 'none';
                if ($captcha_type !== 'none' && !$captcha_handler->verify_captcha( $captcha_response_token )) {
                     return new WP_Error('slr_captcha_error', __('کپچا تایید نشد.', 'yakutlogin'));
                }
            }


            if ( class_exists('Sms_Login_Register_Otp_Handler') && Sms_Login_Register_Otp_Handler::verify_otp( $identifier_for_otp, $submitted_otp ) ) {
                $user_obj_to_login = null;
                if (is_email($identifier_for_otp)) {
                    $user_obj_to_login = get_user_by( 'email', $identifier_for_otp );
                } else if (strpos($identifier_for_otp, '+') === 0 && $gateway_manager) { // It's a normalized phone
                    $users_by_phone = get_users(array('meta_key' => 'slr_phone_number', 'meta_value' => $identifier_for_otp, 'number' => 1, 'count_total' => false));
                    if (!empty($users_by_phone)) $user_obj_to_login = $users_by_phone[0];
                }
                // Fallback to username field if user was found by phone, but login was via username field originally
                if (!$user_obj_to_login && !is_email($identifier_from_username_field)) {
                     $user_obj_to_login = get_user_by('login', $identifier_from_username_field);
                }


                if ( $user_obj_to_login ) {
                    Sms_Login_Register_Otp_Handler::delete_otp( $identifier_for_otp );
                    return $user_obj_to_login;
                } else {
                    return new WP_Error( 'slr_otp_error', __( 'اکانت شما وجود ندارد.', 'yakutlogin' ) );
                }
            } else {
                return new WP_Error( 'slr_otp_error', __( 'کد تایید شما نادرست میباشد', 'yakutlogin' ) );
            }
        } elseif ( isset( $_POST['slr_action'] ) && $_POST['slr_action'] === 'login_with_otp' && ( !isset($_POST['slr_otp_code']) || empty($_POST['slr_otp_code']) ) ) {
             return new WP_Error( 'slr_otp_error', __( 'لطفا کد تایید ارسال شده به ایمیل یا شماره همراه را وارد کنید', 'yakutlogin' ) );
        }
        return $user;
    }


    /**
     * Validates OTP during user registration (for wp-login.php).
     * (No changes from Phase 13 needed here)
     */
    public function validate_registration_with_otp( $errors, $sanitized_user_login, $user_email ) {
        // ... (کد قبلی با بررسی کپچا بدون تغییر) ...
        if ( isset( $_POST['slr_action'] ) && $_POST['slr_action'] === 'register_with_otp' ) {
            // CAPTCHA for wp-login.php registration form submission
            $captcha_handler = class_exists('SLR_Captcha_Handler') ? new SLR_Captcha_Handler() : null;
            $captcha_response_token = isset( $_POST['g-recaptcha-response'] ) ? $_POST['g-recaptcha-response'] : (isset($_POST['cf-turnstile-response']) ? $_POST['cf-turnstile-response'] : '');
            if ($captcha_handler) {
                $options = get_option('slr_plugin_options');
                $captcha_type = isset( $options['captcha_type'] ) ? $options['captcha_type'] : 'none';
                if ($captcha_type !== 'none' && !$captcha_handler->verify_captcha( $captcha_response_token )) {
                    $errors->add('slr_captcha_error', __('کپچا تایید نشد.', 'yakutlogin'));
                    return $errors; // Return early if CAPTCHA fails
                }
            }

            $submitted_otp = isset($_POST['slr_otp_code']) ? sanitize_text_field( $_POST['slr_otp_code'] ) : '';
            $phone_for_otp = isset($_POST['slr_phone_wp_register']) ? sanitize_text_field($_POST['slr_phone_wp_register']) : '';
            $identifier_for_otp = $user_email; // Default to email from registration form

            $gateway_manager = class_exists('SLR_Gateway_Manager') ? new SLR_Gateway_Manager() : null;
            if (!empty($phone_for_otp) && $gateway_manager) {
                $normalized_phone = $gateway_manager->normalize_iranian_phone($phone_for_otp);
                if ($normalized_phone) {
                    // If phone is provided for registration OTP, it should be the primary identifier for verification
                    $identifier_for_otp = $normalized_phone;
                }
            }

            if ( empty($submitted_otp) ) {
                $errors->add( 'slr_otp_required', __( 'لطفا کد تایید ارسال شده به ایمیل یا تلفن همراه را وارد کنید', 'yakutlogin' ) );
            } elseif ( class_exists('Sms_Login_Register_Otp_Handler') && !Sms_Login_Register_Otp_Handler::verify_otp( $identifier_for_otp, $submitted_otp ) ) {
                $errors->add( 'slr_otp_error', __( 'کد تایید وارد شده نادرست میباشد', 'yakutlogin' ) );
            } else {
                if (class_exists('Sms_Login_Register_Otp_Handler')) Sms_Login_Register_Otp_Handler::delete_otp( $identifier_for_otp );
                // If phone was used for OTP and it's a new registration, save it to user meta.
                // This will be done after user is created by WordPress core.
                // We can hook into 'user_register' to save the phone if $identifier_for_otp was phone.
                if (strpos($identifier_for_otp, '+') === 0 && $gateway_manager) { // Was a phone number
                    // Store this phone to be saved later on 'user_register' hook
                    set_transient('slr_pending_phone_for_user_' . $sanitized_user_login, $identifier_for_otp, HOUR_IN_SECONDS);
                }
            }
        }
        return $errors;
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
        $captcha_handler = new SLR_Captcha_Handler();
        $captcha_response_token = isset( $_POST['g-recaptcha-response'] ) ? $_POST['g-recaptcha-response'] : (isset($_POST['cf-turnstile-response']) ? $_POST['cf-turnstile-response'] : '');
        if ( ! $captcha_handler->verify_captcha( $captcha_response_token ) ) {
            wp_send_json_error( array( 'message' => __( 'تایید کپچا ناموفق بود.', 'yakutlogin' ) ) );
            return;
        }

        // ۳. دریافت و اعتبارسنجی ورودی‌ها
        $identifier_input = isset( $_POST['identifier'] ) ? $_POST['identifier'] : '';
        $otp_code         = isset( $_POST['otp_code'] ) ? sanitize_text_field( $_POST['otp_code'] ) : '';
        $redirect_to      = isset( $_POST['redirect_to'] ) ? esc_url_raw( $_POST['redirect_to'] ) : '';

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
     * Saves the phone number from transient to user meta after registration on wp-login.php.
     *
     * @since 1.0.4
     * @param int $user_id User ID.
     */
    public function save_pending_phone_number_on_registration( $user_id ) {
        $user = get_userdata( $user_id );
        if ( ! $user ) {
            return;
        }

        $transient_key = 'slr_pending_phone_for_user_' . $user->user_login;
        $phone_number = get_transient( $transient_key );

        if ( $phone_number ) {
            // Ensure it's a normalized phone number (should already be, but double check)
            $gateway_manager = class_exists('SLR_Gateway_Manager') ? new SLR_Gateway_Manager() : null;
            if ($gateway_manager) {
                $normalized_phone = $gateway_manager->normalize_iranian_phone($phone_number);
                if ($normalized_phone) {
                    update_user_meta( $user_id, 'slr_phone_number', $normalized_phone );
                }
            } else { // Fallback if gateway manager somehow not available
                 update_user_meta( $user_id, 'slr_phone_number', $phone_number );
            }
            delete_transient( $transient_key );
        }
    }
}