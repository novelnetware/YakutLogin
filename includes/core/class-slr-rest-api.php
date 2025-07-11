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
    // Webhook endpoint for Telegram bot
    register_rest_route( $this->namespace, '/telegram-webhook', [
      'methods'       => WP_REST_Server::CREATABLE, // Accepts POST requests
      'callback'       => [ $this, 'handle_telegram_webhook' ],
      'permission_callback' => '__return_true', // Publicly accessible but we will verify internally
    ] );

    // Webhook endpoint for Bale bot
        register_rest_route($this->namespace, '/bale-webhook', [
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => [$this, 'handle_bale_webhook'],
            'permission_callback' => '__return_true',
        ]);

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

  // Add this new method to the SLR_Rest_Api class
    public function handle_bale_webhook(WP_REST_Request $request) {
        $update = json_decode($request->get_body(), true);

        if (!$update || !isset($update['message']) || !isset($update['message']['chat']['id'])) {
            return new WP_REST_Response(['status' => 'ok'], 200); // Ignore invalid updates
        }

        $message = $update['message'];
        $chat_id = $message['chat']['id'];
        $bale_handler = new SLR_Bale_Handler();

        // Case 1: User sends the 6-digit code
        if (isset($message['text']) && is_numeric($message['text']) && strlen($message['text']) === 6) {
            $unique_code = $message['text'];
            $session_id = get_transient('slr_bale_code_' . $unique_code);

            if ($session_id) {
                set_transient('slr_bale_chat_session_' . $chat_id, $session_id, 5 * MINUTE_IN_SECONDS);
                delete_transient('slr_bale_code_' . $unique_code);
                $bale_handler->request_contact_info($chat_id, 'متشکرم. برای تکمیل ورود، لطفا شماره تلفن خود را با فشردن دکمه زیر به اشتراک بگذارید.');
            } else {
                $bale_handler->send_message($chat_id, 'کد وارد شده نامعتبر یا منقضی شده است. لطفا مجددا از سایت اقدام کنید.');
            }
        }
        // Case 2: User shares their contact
        elseif (isset($message['contact']['phone_number'])) {
            $phone_number = $message['contact']['phone_number'];
            $normalized_phone = (new SLR_Gateway_Manager())->normalize_iranian_phone($phone_number);
            
            $session_id = get_transient('slr_bale_chat_session_' . $chat_id);
            if ($session_id && $normalized_phone) {
                $users = get_users(['meta_key' => 'slr_phone_number', 'meta_value' => $normalized_phone, 'number' => 1]);

                if (empty($users)) {
                    update_transient('slr_bale_session_' . $session_id, ['status' => 'failed']);
                    $bale_handler->send_message($chat_id, 'حسابی با این شماره تلفن یافت نشد.');
                } else {
                    update_transient('slr_bale_session_' . $session_id, [
                        'status'  => 'success',
                        'user_id' => $users[0]->ID,
                    ]);
                    $bale_handler->send_message($chat_id, 'تایید شد! شما با موفقیت وارد شدید. به وب‌سایت بازگردید.');
                }
                delete_transient('slr_bale_chat_session_' . $chat_id);
            }
        }

        return new WP_REST_Response(['status' => 'success'], 200);
    }

  /**
  * Handles incoming updates from the Telegram bot.
  *
  * @param WP_REST_Request $request The incoming request object.
  * @return WP_REST_Response
  */
 public function handle_telegram_webhook( WP_REST_Request $request ) {
        $options = get_option('slr_plugin_options', []);

        // SECURITY: If CF Worker is enabled, we MUST validate the secret header.
        if (!empty($options['telegram_use_cf_worker'])) {
            $proxy_secret = $options['telegram_cf_proxy_secret'] ?? null;
            $received_secret = $request->get_header('X-Yakut-Proxy-Secret');

            if (empty($proxy_secret) || empty($received_secret) || !hash_equals($proxy_secret, $received_secret)) {
                // If the secret is missing or incorrect, deny access.
                return new WP_REST_Response(['status' => 'error', 'message' => 'Forbidden: Invalid proxy secret.'], 403);
            }
        }

        $update = json_decode($request->get_body(), true);

        if (!$update || !isset($update['message'])) {
            return new WP_REST_Response(['status' => 'error', 'message' => 'Invalid update.'], 400);
        }

        $message = $update['message'];
        $chat_id = $message['chat']['id'];
        $telegram_handler = new SLR_Telegram_Handler(); // Uses saved token

        // Case 1: User sends /start command with the unique key
        if (isset($message['text']) && strpos($message['text'], '/start') === 0) {
            $parts = explode(' ', $message['text']);
            $unique_key = $parts[1] ?? null;

            if (!$unique_key) {
                return new WP_REST_Response(['status' => 'ok'], 200);
            }

            $session_id = get_transient('slr_tg_key_' . $unique_key);
            if (false === $session_id) {
                $telegram_handler->send_message($chat_id, __('This login link has expired. Please try again.', 'yakutlogin'));
                return new WP_REST_Response(['status' => 'error', 'message' => 'Expired key.'], 200);
            }
            
            set_transient('slr_tg_chat_session_' . $chat_id, $session_id, 5 * MINUTE_IN_SECONDS);
            delete_transient('slr_tg_key_' . $unique_key);

            $telegram_handler->send_message(
                $chat_id,
                __('Welcome! To log in, please press the button below to share your phone number.', 'yakutlogin'),
                // We can add a keyboard button here to make it easier for the user
            );

        // Case 2: User shares their contact (phone number)
        } elseif (isset($message['contact'])) {
            $phone_number = $message['contact']['phone_number'];
            $normalized_phone = (new SLR_Gateway_Manager())->normalize_iranian_phone($phone_number);
            
            $session_id = get_transient('slr_tg_chat_session_' . $chat_id);
            if (false === $session_id) {
                 $telegram_handler->send_message($chat_id, __('Your session has expired. Please start the login process on the website again.', 'yakutlogin'));
                 return new WP_REST_Response(['status' => 'error', 'message' => 'Expired chat session.'], 200);
            }
            
            if (!$normalized_phone) {
                $telegram_handler->send_message($chat_id, __('The phone number format is not valid.', 'yakutlogin'));
                return new WP_REST_Response(['status' => 'error', 'message' => 'Invalid phone.'], 200);
            }

            $users = get_users(['meta_key' => 'slr_phone_number', 'meta_value' => $normalized_phone, 'number' => 1]);

            if (empty($users)) {
                $telegram_handler->send_message($chat_id, __('No account was found with this phone number.', 'yakutlogin'));
                update_transient('slr_tg_session_' . $session_id, ['status' => 'failed']);
            } else {
                $user = $users[0];
                update_transient('slr_tg_session_' . $session_id, [
                    'status'  => 'success',
                    'user_id' => $user->ID,
                ]);
                $telegram_handler->send_message($chat_id, __('Thank you! You are now logged in. Please return to the website.', 'yakutlogin'));
            }
            delete_transient('slr_tg_chat_session_' . $chat_id);
        }

        return new WP_REST_Response(['status' => 'success'], 200);
    }
}