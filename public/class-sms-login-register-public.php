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
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $plugin_name    The ID of this plugin.
     */
    private $plugin_name;

    /**
     * The version of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $version    The current version of this plugin.
     */
    private $version;

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     * @param    string    $plugin_name       The name of the plugin.
     * @param    string    $version    The version of this plugin.
     */
    public function __construct( $plugin_name, $version ) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
    }

    // Removed empty and commented out enqueue_styles() method as maybe_enqueue_scripts() handles CSS.

    private static $scripts_enqueued = false; // Flag to ensure scripts are enqueued only once

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
                    <div class="slr-form-row slr-social-login-row slr-google-login-row">
                        <a href="<?php echo esc_url( $google_login_url ); ?>" class="slr-button slr-google-login-button">
                            <svg aria-hidden="true" class="slr-google-icon" width="18" height="18" viewBox="0 0 18 18"><path d="M16.51 8.25H9v3.03h4.3c-.38 1.17-.95 2.03-1.88 2.66v2.01h2.6c1.51-1.38 2.38-3.48 2.38-5.88 0-.57-.05-.66-.15-1.14z" fill="#4285F4"></path><path d="M9 16.5c2.44 0 4.47-.8 5.96-2.18l-2.6-2.01c-.8.54-1.84.86-3.36.86-2.58 0-4.76-1.73-5.54-4.06H.96v2.01C2.45 14.49 5.48 16.5 9 16.5z" fill="#34A853"></path><path d="M3.46 10.39c-.19-.54-.3-.94-.3-1.39s.11-.85.3-1.39V5.58H.96c-.66 1.33-1.04 2.79-1.04 4.42s.38 3.09 1.04 4.42l2.5-2.01z" fill="#FBBC05"></path><path d="M9 3.54c1.34 0 2.53.46 3.48 1.38l2.3-2.3C13.46.89 11.43 0 9 0 5.48 0 2.45 2.01.96 5.02l2.5 2.01C4.24 5.27 6.42 3.54 9 3.54z" fill="#EA4335"></path></svg>
                            <?php echo esc_html( $args['button_texts']['google'] ); ?>
                        </a>
                    </div>
                    <div class="slr-form-row slr-divider-row">
                        <span class="slr-divider-text"><?php _e('یا', 'yakutlogin'); ?></span>
                    </div>
                <?php endif; ?>
                
                <?php
                // Helper function/logic to render a field based on layout
                $render_field_logic = function($field_type, $field_name, $default_label_text, $default_placeholder_text) use ($args) {
                    $field_id = 'slr_' . $field_name . '_' . $args['form_id'];
                    $input_type_attr = ($field_type === 'phone') ? 'tel' : (($field_type === 'otp') ? 'text' : 'email');
                    
                    $label_text = $default_label_text; // Can be customized further if needed
                    $placeholder_text = ($args['show_labels'] && $args['layout'] === 'inline_labels') ? $label_text : $default_placeholder_text;
                    
                    echo '<div class="slr-form-row slr-' . esc_attr($field_type) . '-row">';
                    if ($args['show_labels'] && $args['layout'] !== 'inline_labels') {
                        echo '<label for="' . esc_attr($field_id) . '">' . esc_html($label_text) . '</label>';
                    }
                    echo '<input type="' . esc_attr($input_type_attr) . '" name="slr_' . esc_attr($field_name) . '" id="' . esc_attr($field_id) . '" class="slr-input slr-' . esc_attr($field_name) . '-input" placeholder="' . esc_attr($placeholder_text) . '" ' . ($input_type_attr === 'text' && $field_type === 'otp' ? 'autocomplete="off"' : '') . ' />';
                    echo '</div>';
                };

                // Render fields - order can be customized by Elementor controls later if needed
                $render_field_logic('phone', 'phone', __('تلفن همراه', 'yakutlogin'), __('مثال : 09123456789', 'yakutlogin'));
                $render_field_logic('email', 'email', __('ایمیل', 'yakutlogin'), __('ایمیل خود را وارد کنید', 'yakutlogin'));
                ?>

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
                
                <?php wp_nonce_field( 'slr_send_otp_nonce', 'slr_send_otp_nonce_field' ); ?>
                <?php wp_nonce_field( 'slr_process_form_nonce', 'slr_process_form_nonce_field' ); ?>
                <input type="hidden" name="slr_form_action" value="process_otp_login_register" />
                <input type="hidden" name="slr_form_context" value="<?php echo esc_attr($args['context']); ?>" />
                <?php if (!empty($args['redirect_to'])): ?><input type="hidden" name="slr_redirect_to" value="<?php echo esc_url($args['redirect_to']); ?>" /><?php endif; ?>
            </form>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Enqueues scripts and styles if not already done.
     * Can be called by shortcodes or widgets when they are rendered.
     *
     * @since 1.0.1
     * @updated 1.0.4 (Phase 13: Corrected wp_localize_script placement)
     */
    public function maybe_enqueue_scripts() {
        if ( self::$scripts_enqueued ) {
            $this->enqueue_captcha_scripts_if_needed(); // Still enqueue CAPTCHA if base is done
            return;
        }

        wp_enqueue_script(
            $this->plugin_name . '-public',
            SLR_PLUGIN_URL . 'public/js/sms-login-register-public.js',
            array( 'jquery' ),
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

        // wp_localize_script should be called only once after the main script is enqueued.
        wp_localize_script(
            $this->plugin_name . '-public',
            'slr_public_data',
            array(
                'ajax_url' => admin_url( 'admin-ajax.php' ),
                'send_otp_nonce' => wp_create_nonce( 'slr_send_otp_nonce' ),
                'process_form_nonce' => wp_create_nonce( 'slr_process_form_nonce' ),
                'text_sending_otp' => __( 'ارسال کد تایید...', 'yakutlogin' ),
                'text_processing' => __( 'درحال پردازش...', 'yakutlogin' ),
                'text_otp_sent' => __( 'کد تایید ارسال شد', 'yakutlogin' ), // Updated text
                'text_error_sending_otp' => __( 'خطا در ارسال کد تایید . لطفا بعدا مجدد امتحان کنید', 'yakutlogin' ),
                'text_invalid_email' => __( 'لطفا یک ایمیل معتبر وارد کنید', 'yakutlogin' ),
                'text_invalid_phone' => __( 'لطفا یک شماره تلفن معتبر وارد کنید', 'yakutlogin' ), // Added
                'text_fill_otp' => __( 'لطفا کد تایید را وارد کنید', 'yakutlogin'),
                'text_login_success' => __('با موفقیت وارد شدید', 'yakutlogin'),
                'text_registration_success' => __('ثبت نام شما با موفقیت انجام شد', 'yakutlogin'),
            )
        );
        
        self::$scripts_enqueued = true;
    }

    /**
     * Enqueues CAPTCHA API scripts if a CAPTCHA service is enabled.
     * @since 1.0.3
     */
    private function enqueue_captcha_scripts_if_needed() {
        $options = get_option( 'slr_plugin_options' );
        $captcha_type = isset( $options['captcha_type'] ) ? $options['captcha_type'] : 'none';

        if ( $captcha_type === 'recaptcha_v2' && !empty($options['recaptcha_v2_site_key']) ) {
            if (!wp_script_is('google-recaptcha', 'enqueued')) {
                wp_enqueue_script( 'google-recaptcha', 'https://www.google.com/recaptcha/api.js?render=explicit&onload=slrRenderReCaptcha', array(), null, true );
                // Added onload callback for explicit rendering
            }
        } elseif ( $captcha_type === 'turnstile' && !empty($options['turnstile_site_key']) ) {
             if (!wp_script_is('cloudflare-turnstile', 'enqueued')) {
                // Cloudflare recommends adding async defer directly to script tag if possible,
                // but for wp_enqueue_script, we can rely on it being in footer or use wp_script_add_data.
                wp_enqueue_script( 'cloudflare-turnstile', 'https://challenges.cloudflare.com/turnstile/v0/api.js?render=explicit&onload=slrRenderTurnstile', array(), null, true );
            }
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
     * (No changes from Phase 13 needed for its internal logic, but it uses Captcha_Handler)
     */
    public function ajax_send_otp() {
        // ... (کد قبلی با بررسی کپچا بدون تغییر) ...
        check_ajax_referer( 'slr_send_otp_nonce', 'security' );

        $captcha_handler = null;
        if (class_exists('SLR_Captcha_Handler')) {
            $captcha_handler = new SLR_Captcha_Handler();
        }
        $captcha_response_token = isset( $_POST['g-recaptcha-response'] ) ? $_POST['g-recaptcha-response'] : (isset($_POST['cf-turnstile-response']) ? $_POST['cf-turnstile-response'] : '');
        
        if ( $captcha_handler && !$captcha_handler->verify_captcha( $captcha_response_token ) ) {
            wp_send_json_error( array( 'message' => __( 'کپچا تایید نشد . لطفا مجدد تلاش کنید', 'yakutlogin' ) ) );
            return;
        }

        $email = isset( $_POST['email'] ) ? sanitize_email( $_POST['email'] ) : '';
        $phone = isset( $_POST['phone'] ) ? sanitize_text_field( $_POST['phone'] ) : '';
        
        $options = get_option( 'slr_plugin_options' );
        $gateway_manager = null;
        if (class_exists('SLR_Gateway_Manager')) {
            $gateway_manager = new SLR_Gateway_Manager();
        }
        $active_sms_provider = $gateway_manager ? $gateway_manager->get_active_gateway_id() : null;

        $identifier = '';
        $send_method = '';

        if ( !empty($phone) && $active_sms_provider && $gateway_manager ) {
            $normalized_phone = $gateway_manager->normalize_iranian_phone($phone);
            if ($normalized_phone) {
                $identifier = $normalized_phone;
                $send_method = 'sms';
            } else {
                 wp_send_json_error( array( 'message' => __( 'تلفن همراه نامعتبر میباشد', 'yakutlogin' ) ) );
                 return;
            }
        } elseif (!empty($email) && is_email($email) && isset($options['email_otp_enabled']) && $options['email_otp_enabled']) {
            $identifier = $email;
            $send_method = 'email';
        } else {
            if (empty($phone) && empty($email)) {
                 wp_send_json_error( array( 'message' => __( 'ایمیل یا شماره موبایل خود را وارد کنید', 'yakutlogin' ) ) );
            } elseif (!empty($phone) && !$active_sms_provider) {
                 wp_send_json_error( array( 'message' => __( 'سرویس پیامکی فعال نمیباشد . لطفا با مدیریت تماس بگیرید', 'yakutlogin' ) ) );
            } elseif (!empty($email) && (!isset($options['email_otp_enabled']) || !$options['email_otp_enabled'])) {
                 wp_send_json_error( array( 'message' => __( 'ارسال کد تایید با ایمیل غیرفعال میباشد', 'yakutlogin' ) ) );
            } else {
                 wp_send_json_error( array( 'message' => __( 'لطفا ایمیل یا تلفن همراه خود را مجددا چک کنید', 'yakutlogin' ) ) );
            }
            return;
        }

        if ( class_exists('Sms_Login_Register_Otp_Handler') && Sms_Login_Register_Otp_Handler::is_on_cooldown( $identifier, 60 ) ) {
            wp_send_json_error( array( 'message' => __( 'لطفا 60 ثانیه صبر کنید تا بتوانید کد جدید ارسال کنید', 'yakutlogin' ) ) );
            return;
        }

        $otp = class_exists('Sms_Login_Register_Otp_Handler') ? Sms_Login_Register_Otp_Handler::generate_otp() : rand(100000,999999);
        $stored = class_exists('Sms_Login_Register_Otp_Handler') ? Sms_Login_Register_Otp_Handler::store_otp( $identifier, $otp ) : false;

        if ( ! $stored ) {
            wp_send_json_error( array( 'message' => __( 'لطفا مجددا تلاش کنید', 'yakutlogin' ) ) );
            return;
        }

        $sent_successfully = false;
        if ($send_method === 'sms' && $gateway_manager) {
            $sent_successfully = $gateway_manager->send_otp( $identifier, $otp );
            $message_on_success = __( 'کد تایید با موفقیت ارسال شد', 'yakutlogin' );
            $message_on_fail = __( 'امکان ارسال کد تایید وجود ندارد', 'yakutlogin' );
        } elseif ($send_method === 'email') {
            $sent_successfully = $this->send_otp_email( $identifier, $otp );
            $message_on_success = __( 'کد تایید به ایمیل شما ارسال شد', 'yakutlogin' );
            $message_on_fail = __( 'امکان ارسال کد تایید وجود ندارد', 'yakutlogin' );
        }

        if ( $sent_successfully ) {
            wp_send_json_success( array( 'message' => $message_on_success ) );
        } else {
            if (class_exists('Sms_Login_Register_Otp_Handler')) Sms_Login_Register_Otp_Handler::delete_otp( $identifier );
            wp_send_json_error( array( 'message' => $message_on_fail ) );
        }
    }

    /**
     * Authenticates a user with OTP if provided (for wp-login.php).
     * (No changes from Phase 13 needed here)
     */
    public function authenticate_with_otp( $user, $username, $password ) {
        // ... (کد قبلی بدون تغییر) ...
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
     * AJAX handler for processing OTP login/registration from generic forms.
     * (No changes from Phase 13 needed for its internal logic, but it uses Captcha_Handler)
     */
    public function ajax_process_login_register_otp() {
        // ... (کد قبلی با بررسی کپچا بدون تغییر) ...
        check_ajax_referer( 'slr_process_form_nonce', 'slr_process_form_nonce_field' );

        $captcha_handler = class_exists('SLR_Captcha_Handler') ? new SLR_Captcha_Handler() : null;
        $captcha_response_token = isset( $_POST['g-recaptcha-response'] ) ? $_POST['g-recaptcha-response'] : (isset($_POST['cf-turnstile-response']) ? $_POST['cf-turnstile-response'] : '');

        if ( $captcha_handler && !$captcha_handler->verify_captcha( $captcha_response_token ) ) {
            wp_send_json_error( array( 'message' => __( 'کپچا تایید نشد.', 'sms-login-register' ) ) );
            return;
        }

        $email = isset( $_POST['email'] ) ? sanitize_email( $_POST['email'] ) : '';
        $phone_input = isset( $_POST['phone'] ) ? sanitize_text_field( $_POST['phone'] ) : '';
        $otp_code = isset( $_POST['otp_code'] ) ? sanitize_text_field( $_POST['otp_code'] ) : '';
        $redirect_to_custom = isset($_POST['redirect_to']) ? esc_url_raw($_POST['redirect_to']) : '';

        $identifier = '';
        $login_method = '';

        $gateway_manager = class_exists('SLR_Gateway_Manager') ? new SLR_Gateway_Manager() : null;

        if (!empty($phone_input) && $gateway_manager) {
            $normalized_phone = $gateway_manager->normalize_iranian_phone($phone_input);
            if ($normalized_phone) {
                $identifier = $normalized_phone;
                $login_method = 'phone';
            }
        }
        
        if (empty($identifier) && !empty($email) && is_email($email)) {
            $identifier = $email;
            $login_method = 'email';
        }

        if ( empty( $identifier ) ) {
            wp_send_json_error( array( 'message' => __( 'ایمیل یا شماره تلفن معتبر وارد کنید', 'yakutlogin' ) ) );
            return;
        }

        // The original code had a duplicate check for email emptiness after identifier logic.
        // The check above for empty($identifier) is sufficient.
        // if ( empty( $email ) || ! is_email( $email ) ) {
        //     wp_send_json_error( array( 'message' => __( 'Invalid email address.', 'sms-login-register' ) ) );
        //     return;
        // }

        if ( empty( $otp_code ) ) {
            wp_send_json_error( array( 'message' => __( 'کد تایید نمیتواند خالی بماند', 'yakutlogin' ) ) );
            return;
        }

        if ( class_exists('Sms_Login_Register_Otp_Handler') && !Sms_Login_Register_Otp_Handler::verify_otp( $identifier, $otp_code ) ) {
            wp_send_json_error( array( 'message' => __( 'کد تایید منقضی شده است . مجددا ارسال کنید', 'yakutlogin' ) ) );
            return;
        }

        $user = null;
        if ($login_method === 'email') {
            $user = get_user_by( 'email', $identifier );
        } elseif ($login_method === 'phone' && $gateway_manager) {
            $users = get_users(array(
                'meta_key' => 'slr_phone_number',
                'meta_value' => $identifier,
                'number' => 1,
                'count_total' => false
            ));
            if (!empty($users)) {
                $user = $users[0];
            }
        }

        if ( $user ) {
            wp_clear_auth_cookie();
            wp_set_current_user( $user->ID );
            wp_set_auth_cookie( $user->ID, true );
            if (class_exists('Sms_Login_Register_Otp_Handler')) Sms_Login_Register_Otp_Handler::delete_otp( $identifier );
            do_action( 'wp_login', $user->user_login, $user );


            $redirect_url = !empty($redirect_to_custom) ? $redirect_to_custom : apply_filters('slr_login_redirect_url_default', admin_url(), $user);
            wp_send_json_success( array( 
                'message' => __( 'با موفقیت وارد شدید . درحال انتقال....', 'sms-login-register' ),
                'redirect_url' => apply_filters('slr_login_redirect_url', $redirect_url, $user)
            ) );
        } else {
            $user_id = 0;
            if ($login_method === 'email') {
                $username = sanitize_user( explode( '@', $identifier )[0] . wp_rand(10,999), true );
                $random_password = wp_generate_password( 12, false );
                $user_id = wp_create_user( $username, $random_password, $identifier );
            } elseif ($login_method === 'phone' && $gateway_manager) {
                $temp_email_for_registration = $email; // Use email if provided alongside phone
                if (empty($temp_email_for_registration) || !is_email($temp_email_for_registration)) {
                     $temp_email_for_registration = 'user_' . time() . '@' . wp_parse_url(home_url(), PHP_URL_HOST);
                }
                $username = sanitize_user( 'user_' . preg_replace('/[^0-9]/','', $identifier) . wp_rand(10,99), true );
                $random_password = wp_generate_password( 12, false );
                $user_id = wp_create_user( $username, $random_password, $temp_email_for_registration );
                
                if ( !is_wp_error($user_id) ) {
                    update_user_meta($user_id, 'slr_phone_number', $identifier);
                     if ($temp_email_for_registration !== $email && strpos($temp_email_for_registration, '@' . wp_parse_url(home_url(), PHP_URL_HOST)) !== false) {
                        update_user_meta($user_id, 'slr_requires_email_update', true);
                    }
                }
            } else {
                 wp_send_json_error( array( 'message' => __( 'امکان ثبت نام بدون ایمیل یا شماره تلفن وجود ندارد', 'yakutlogin' ) ) );
                 return;
            }

            if ( is_wp_error( $user_id ) ) {
                wp_send_json_error( array( 'message' => $user_id->get_error_message() ) );
                return;
            }
            
            $new_user = get_user_by('id', $user_id);
            wp_clear_auth_cookie();
            wp_set_current_user( $user_id , $new_user->user_login);
            wp_set_auth_cookie( $user_id, true );
            if (class_exists('Sms_Login_Register_Otp_Handler')) Sms_Login_Register_Otp_Handler::delete_otp( $identifier ); 
            do_action( 'wp_login', $new_user->user_login, $new_user );
            
            $redirect_url = !empty($redirect_to_custom) ? $redirect_to_custom : apply_filters('slr_registration_redirect_url_default', admin_url(), $new_user);
            wp_send_json_success( array(
                'message' => __( 'ثبت نام با موفقیت انجام شد . درحال انتقال....', 'yakutlogin' ),
                'redirect_url' => apply_filters('slr_registration_redirect_url', $redirect_url, $new_user)
            ) );
        }
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