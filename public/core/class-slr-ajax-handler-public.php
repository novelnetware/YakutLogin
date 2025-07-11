<?php
/**
 * Handles all public-facing AJAX requests, such as OTP, Telegram, and Bale interactions.
 *
 * @package     Sms_Login_Register
 * @subpackage  Sms_Login_Register/public/core
 * @since       1.4.0
 */
if ( ! defined( 'ABSPATH' ) ) exit;

class SLR_Ajax_Handler_Public {

    public function __construct() {
        $this->init_hooks();
    }

    /**
     * Register all public AJAX action hooks.
     */
    public function init_hooks() {
        $ajax_actions = [
            'slr_send_otp'                      => 'handle_send_otp',
            'slr_process_login_register_otp'    => 'handle_process_otp',
            'slr_generate_telegram_request'     => 'handle_generate_telegram_request',
            'slr_check_telegram_login_status'   => 'handle_check_telegram_status',
            'slr_send_bale_otp'                 => 'handle_send_bale_otp',
            'slr_generate_bale_bot_request'     => 'handle_generate_bale_request',
            'slr_check_bale_login_status'       => 'handle_check_bale_status',
        ];

        foreach ($ajax_actions as $action => $handler) {
            add_action('wp_ajax_' . $action, [$this, $handler]);
            add_action('wp_ajax_nopriv_' . $action, [$this, $handler]);
        }
    }

    /**
     * AJAX handler for sending OTP via SMS or Email.
     */
    public function handle_send_otp() {
        check_ajax_referer('slr_send_otp_nonce', 'security');

        if (class_exists('SLR_Captcha_Handler')) {
            $captcha_token = isset($_POST['g-recaptcha-response']) ? $_POST['g-recaptcha-response'] : ($_POST['cf-turnstile-response'] ?? '');
            if (!(new SLR_Captcha_Handler())->verify_captcha($captcha_token)) {
                wp_send_json_error(['message' => __('تایید کپچا ناموفق بود.', 'yakutlogin')]);
            }
        }
        
        $identifier_input = isset($_POST['identifier']) ? $_POST['identifier'] : '';
        $identifier_data = SLR_User_Handler::determine_identifier_type($identifier_input);
        if ($identifier_data['type'] === 'invalid') {
            wp_send_json_error(['message' => __('فرمت ورودی نامعتبر است.', 'yakutlogin')]);
        }

        $identifier = $identifier_data['value'];
        if (Sms_Login_Register_Otp_Handler::is_on_cooldown($identifier, 60)) {
            wp_send_json_error(['message' => __('شما به تازگی یک کد دریافت کرده‌اید. لطفا ۶۰ ثانیه صبر کنید.', 'yakutlogin')]);
        }

        $otp = Sms_Login_Register_Otp_Handler::generate_otp();
        Sms_Login_Register_Otp_Handler::store_otp($identifier, $otp);

        $sent = false;
        if ($identifier_data['type'] === 'phone') {
            $gateway_manager = new SLR_Gateway_Manager();
            if ($gateway_manager->get_active_gateway()) {
                $sent = $gateway_manager->send_otp($identifier, $otp);
            }
        } else { // 'email'
            $sent = (new Sms_Login_Register_Public(SLR_PLUGIN_NAME_FOR_INSTANCE, SLR_PLUGIN_VERSION_FOR_INSTANCE, SLR_Theme_Manager::get_instance()))->send_otp_email($identifier, $otp);
        }

        if ($sent) {
            wp_send_json_success(['message' => __('کد تایید با موفقیت ارسال شد.', 'yakutlogin')]);
        } else {
            Sms_Login_Register_Otp_Handler::delete_otp($identifier);
            wp_send_json_error(['message' => __('ارسال کد ناموفق بود. لطفا تنظیمات را بررسی کنید.', 'yakutlogin')]);
        }
    }

    /**
     * AJAX handler for processing OTP login/registration.
     */
    public function handle_process_otp() {
        check_ajax_referer('slr_process_form_nonce', 'slr_process_form_nonce_field');
        
        $identifier_input = isset($_POST['slr_identifier']) ? $_POST['slr_identifier'] : '';
        $otp_code = isset($_POST['slr_otp_code']) ? sanitize_text_field($_POST['slr_otp_code']) : '';
        
        $identifier_data = SLR_User_Handler::determine_identifier_type($identifier_input);
        if ($identifier_data['type'] === 'invalid') {
            wp_send_json_error(['message' => __('ایمیل یا شماره تلفن نامعتبر است.', 'yakutlogin')]);
        }
        
        if (!Sms_Login_Register_Otp_Handler::verify_otp($identifier_data['value'], $otp_code)) {
            wp_send_json_error(['message' => __('کد تایید اشتباه یا منقضی شده است.', 'yakutlogin')]);
        }
        
        $user = SLR_User_Handler::find_or_create_user($identifier_data['value'], $identifier_data['type']);
        if (is_wp_error($user)) {
            wp_send_json_error(['message' => $user->get_error_message()]);
        }
        
        wp_clear_auth_cookie();
        wp_set_current_user($user->ID);
        wp_set_auth_cookie($user->ID, true);
        do_action('wp_login', $user->user_login, $user);
        
        $redirect_to = isset($_POST['slr_redirect_to']) ? esc_url_raw($_POST['slr_redirect_to']) : '';
        $redirect_url = !empty($redirect_to) ? $redirect_to : apply_filters('slr_login_redirect_url_default', admin_url(), $user);
        
        wp_send_json_success(['redirect_url' => apply_filters('slr_login_redirect_url', $redirect_url, $user)]);
    }
    
    public function handle_generate_telegram_request() {
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

    public function handle_check_telegram_status() {
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
    public function handle_send_bale_otp() {
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
    public function handle_generate_bale_bot_request() {
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
    public function handle_check_bale_login_status() {
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
}