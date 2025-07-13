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
            'slr_send_bale_otp'                 => 'handle_send_bale_otp',
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

    
}