<?php
/**
* Handles custom REST API endpoints for the plugin.
*
* @package  Sms_Login_Register
* @subpackage Sms_Login_Register/includes/core
* @since   1.3.0
*/

if ( ! defined( 'ABSPATH' ) ) {
  exit; // Exit if accessed directly.
}

class SLR_Rest_Api {

  /**
  * The namespace for the custom REST API endpoints.
  * @var string
  */
  private $namespace = 'yakutlogin/v1';

  /**
  * Register the REST API routes.
  */
  public function __construct() {
    add_action( 'rest_api_init', [ $this, 'register_routes' ] );
  }

  /**
  * Registers all custom routes for the plugin.
  */
  public function register_routes() {

        // --- Application API Endpoints ---

        // Endpoint for requesting an OTP
        register_rest_route($this->namespace, '/send-otp', [
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => [$this, 'api_send_otp'],
            'permission_callback' => [$this, 'api_permission_callback'],
        ]);

        // Endpoint for verifying OTP and logging in/registering
        register_rest_route($this->namespace, '/verify-otp', [
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => [$this, 'api_verify_otp'],
            'permission_callback' => [$this, 'api_permission_callback'],
        ]);
  }

  /**
 * Permission callback to authenticate API requests using Public and Secret Keys.
 */
public function api_permission_callback(WP_REST_Request $request) {
    // تغییر ۱: دریافت کلید مخفی به جای امضا (Signature)
    $public_key = $request->get_header('x_yakut_public_key');
    $secret_key_plain = $request->get_header('x_yakut_secret_key'); // دریافت کلید مخفی خام

    // دیگر نیازی به بدنه درخواست برای احراز هویت نداریم
    // $body = $request->get_body(); 

    if (!$public_key || !$secret_key_plain) {
        return new WP_Error('rest_unauthorized', 'API Public Key or Secret Key missing in headers.', ['status' => 401]);
    }

    global $wpdb;
    $table_name = $wpdb->prefix . 'slr_api_keys';

    // تغییر ۲: دریافت هش کلید مخفی از دیتابیس
    $key_data = $wpdb->get_row($wpdb->prepare(
        // مطمئن شوید که نام ستون در دیتابیس شما secret_key_hash است
        "SELECT secret_key_hash FROM {$table_name} WHERE public_key = %s AND status = 'active'",
        $public_key
    ));

    if (!$key_data || !isset($key_data->secret_key_hash)) {
        return new WP_Error('rest_invalid_key', 'Invalid Public Key or Key is inactive.', ['status' => 403]);
    }

    // تغییر ۳: استفاده از wp_check_password به جای hash_hmac
    // مقایسه کلید مخفی خام ارسالی با هش ذخیره شده در دیتابیس
    if (!wp_check_password($secret_key_plain, $key_data->secret_key_hash)) {
        return new WP_Error('rest_invalid_secret', 'Invalid Secret Key.', ['status' => 403]);
    }
    
    // احراز هویت موفق بود
    // Update last_used timestamp
    $wpdb->update($table_name, ['last_used' => current_time('mysql')], ['public_key' => $public_key]);

    return true;
}
    /**
     * API endpoint to send an OTP.
     */
    public function api_send_otp(WP_REST_Request $request) {
        $params = $request->get_json_params();
        $identifier = isset($params['identifier']) ? sanitize_text_field($params['identifier']) : null;

        if (!$identifier) {
            return new WP_Error('missing_identifier', 'Identifier (phone or email) is required.', ['status' => 400]);
        }

        $public_class = new Sms_Login_Register_Public(SLR_PLUGIN_NAME_FOR_INSTANCE, SLR_PLUGIN_VERSION_FOR_INSTANCE, SLR_Theme_Manager::get_instance());
        
        // We can reuse the same logic from the public AJAX handler
        // Note: This assumes the 'slr_send_otp' logic is robust, which it is.
        // For simplicity, we directly call the required handlers here.
        
        $identifier_data = $public_class->determine_identifier_type($identifier);
        $normalized_id = $identifier_data['value'];
        $send_method = $identifier_data['type'];

        if ($send_method === 'invalid') {
            return new WP_Error('invalid_identifier', 'Invalid identifier format.', ['status' => 400]);
        }

        $otp_code = Sms_Login_Register_Otp_Handler::generate_otp();
        Sms_Login_Register_Otp_Handler::store_otp($normalized_id, $otp_code);

        $sent = false;
        if ($send_method === 'phone') {
            $gateway_manager = new SLR_Gateway_Manager();
            if ($gateway_manager->get_active_gateway()) {
                $sent = $gateway_manager->send_otp($normalized_id, '', $otp_code);
            }
        } elseif ($send_method === 'email') {
            $sent = $public_class->send_otp_email($normalized_id, $otp_code);
        }

        if ($sent) {
            return new WP_REST_Response(['success' => true, 'message' => 'OTP sent successfully.'], 200);
        } else {
            Sms_Login_Register_Otp_Handler::delete_otp($normalized_id);
            return new WP_Error('send_failed', 'Failed to send OTP.', ['status' => 500]);
        }
    }

    /**
     * API endpoint to verify an OTP, then log in or register the user,
     * and return an Application Password.
     */
    public function api_verify_otp(WP_REST_Request $request) {
        $params = $request->get_json_params();
        $identifier = $params['identifier'] ?? null;
        $otp_code = $params['otp'] ?? null;

        if (!$identifier || !$otp_code) {
            return new WP_Error('missing_params', 'Identifier and OTP are required.', ['status' => 400]);
        }
        
        $public_class = new Sms_Login_Register_Public(SLR_PLUGIN_NAME_FOR_INSTANCE, SLR_PLUGIN_VERSION_FOR_INSTANCE, SLR_Theme_Manager::get_instance());
        $identifier_data = $public_class->determine_identifier_type($identifier);
        $normalized_id = $identifier_data['value'];

        if (!Sms_Login_Register_Otp_Handler::verify_otp($normalized_id, $otp_code)) {
            return new WP_Error('invalid_otp', 'The OTP is incorrect or has expired.', ['status' => 403]);
        }

        // OTP is valid, find or create the user
        $user = $public_class->find_or_create_user($normalized_id, $identifier_data['type']);

        if (is_wp_error($user)) {
            return $user;
        }

        // User exists, now generate an Application Password
        if (!class_exists('WP_Application_Passwords')) {
            require_once ABSPATH . 'wp-admin/includes/class-wp-application-passwords.php';
        }

        $app_passwords = WP_Application_Passwords::get_user_application_passwords($user->ID);
        // To prevent creating too many passwords, you could reuse one, but for simplicity, we create a new one.
        
        $new_password_data = WP_Application_Passwords::create_new_application_password($user->ID, [
            'name' => 'YakutLogin App (' . gmdate('Y-m-d H:i') . ')',
        ]);
        
        if (is_wp_error($new_password_data)) {
            return $new_password_data;
        }

        // $new_password_data[0] is the raw password. $new_password_data[1] is the hashed item for the DB.
        $app_password = $new_password_data[0];

        $response_data = [
            'success'       => true,
            'message'       => 'Login successful.',
            'app_password'  => $app_password,
            'user_info'     => [
                'id'         => $user->ID,
                'email'      => $user->user_email,
                'username'   => $user->user_login,
                'display_name' => $user->display_name,
            ],
        ];

        return new WP_REST_Response($response_data, 200);
    }

  
}