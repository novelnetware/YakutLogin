<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class SLR_Ajax_Handler {

    private $gateway_manager;

    public function __construct() {

        if (class_exists('SLR_Gateway_Manager')) {
            $this->gateway_manager = new SLR_Gateway_Manager();
        }

        $this->init_hooks();
    }

    /**
     * Register all AJAX action hooks.
     */
    private function init_hooks() {
        $ajax_actions = [
            'yakutlogin_save_settings'      => 'handle_save_settings',
            'yakutlogin_get_gateway_fields' => 'handle_get_gateway_fields',
            'test_telegram_connection'      => 'handle_test_telegram_connection',
            'create_cf_worker'              => 'handle_create_cf_worker',
            'set_telegram_webhook'          => 'handle_set_telegram_webhook',
            'get_telegram_webhook_info'     => 'handle_get_telegram_webhook_info',
            'get_api_keys'                  => 'handle_get_api_keys',
            'generate_api_key'              => 'handle_generate_api_key',
            'revoke_api_key'                => 'handle_revoke_api_key',
            'slr_import_from_digits'        => 'handle_import_from_digits',
            'slr_import_from_wc'            => 'handle_import_from_wc',
            'yakutlogin_cleanup_data'       => 'handle_cleanup_data',
        ];

        foreach ( $ajax_actions as $action => $handler ) {
            add_action( 'wp_ajax_' . $action, [ $this, $handler ] );
        }
    }

    public function handle_save_settings() {
            check_ajax_referer('yakutlogin_admin_nonce', 'nonce');

    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => 'دسترسی غیرمجاز']);
    }

    $all_possible_options = [
        'email_otp_enabled' => 'checkbox',
        'otp_email_subject' => 'text',
        'otp_email_body'    => 'editor',
        'sms_provider'        => 'key',
        'sms_provider_backup' => 'key',
        'kavenegar_api_key'         => 'password',
        'kavenegar_sender_line'     => 'text',
        'kavenegar_use_lookup'      => 'checkbox',
        'kavenegar_lookup_template' => 'text',
        'melipayamak_username'  => 'text',
        'melipayamak_password'  => 'password',
        'melipayamak_from'      => 'text',
        'melipayamak_body_id'   => 'number',
        'melipayamak_is_shared' => 'checkbox',
        'kavansms_api_key' => 'password',
        'kavansms_otp_id'  => 'text',
        'farazsms_username'          => 'text',
        'farazsms_password'          => 'password',
        'farazsms_from'              => 'text',
        'farazsms_pattern_code'      => 'text',
        'farazsms_otp_variable_name' => 'text',
        'smsir_api_key'            => 'password',
        'smsir_template_id'        => 'number',
        'smsir_otp_parameter_name' => 'text',
        'smsir_fast_mode'          => 'checkbox',
        'smsir_line_number'        => 'text',
        'google_login_enabled' => 'checkbox',
        'google_client_id'     => 'text',
        'google_client_secret' => 'password',
        'telegram_login_enabled' => 'checkbox',
        'telegram_bot_token' => 'password',
        'telegram_bot_username'  => 'text',
        'telegram_use_cf_worker' => 'checkbox',
        'telegram_worker_url'    => 'text',
        'telegram_cf_proxy_secret' => 'text',
        'bale_login_enabled'       => 'checkbox',
        'bale_login_mode'          => 'key',
        'bale_bot_token'           => 'password',
        'bale_bot_username'        => 'text',
        'bale_otp_client_id'       => 'text',
        'bale_otp_client_secret'   => 'password',
        'discord_login_enabled'    => 'checkbox',
        'discord_client_id'        => 'text',
        'discord_client_secret'    => 'password',
        'linkedin_login_enabled'   => 'checkbox',
        'linkedin_client_id'       => 'text',
        'linkedin_client_secret'   => 'password',
        'github_login_enabled'     => 'checkbox',
        'github_client_id'         => 'text',
        'github_client_secret'     => 'password',
        'captcha_type'              => 'key',
        'recaptcha_v2_site_key'   => 'text',
        'recaptcha_v2_secret_key' => 'password',
        'turnstile_site_key'      => 'text',
        'turnstile_secret_key'    => 'password',
        'wc_checkout_otp_integration' => 'checkbox',
        'ghasedaksms_api_key'     => 'password',
        'ghasedaksms_line_number' => 'text',
        'ghasedaksms_use_pattern' => 'checkbox',
        'payamresan_username'     => 'text',
        'payamresan_password'     => 'password',
        'payamresan_from'         => 'text',
        'payamresan_use_template' => 'checkbox',
        'sms_otp_template'        => 'text',
    ];


    $submitted_data = [];
    if (isset($_POST['settings'])) {
        parse_str(wp_unslash($_POST['settings']), $submitted_data);
    } else {
        wp_send_json_error(['message' => 'فرمت داده‌ها نامعتبر است']);
    }

    $current_options = get_option('slr_plugin_options', []);

    foreach ($all_possible_options as $key => $type) {
        if ($type === 'checkbox') {
            $current_options[$key] = isset($submitted_data[$key]) ? 1 : 0;
        } elseif (isset($submitted_data[$key])) {
            $value = $submitted_data[$key];
            switch ($type) {
                case 'editor':
            case 'textarea':
                $current_options[$key] = wp_kses_post($value);
                break;
                case 'password':
                case 'text':
                    $current_options[$key] = sanitize_text_field($value);
                    break;
                case 'number':
                    $current_options[$key] = intval($value);
                    break;
                case 'key':
                    $current_options[$key] = sanitize_key($value);
                    break;
                default:
                    $current_options[$key] = sanitize_text_field($value);
            }
        }
    }

    update_option('slr_plugin_options', $current_options);
    
    wp_send_json_success(['message' => 'تنظیمات با موفقیت ذخیره شد!']);
        // $all_possible_options = SLR_Settings_Fields::get_all_fields();
    }
    
/**
     * AJAX handler for getting dynamic gateway fields.
     */
    public function handle_get_gateway_fields() {
        check_ajax_referer('yakutlogin_admin_nonce', 'nonce');
        // Now this check will pass because $this->gateway_manager is initialized.
        if (!current_user_can('manage_options') || !$this->gateway_manager) {
            wp_send_json_error();
        }

        $gateway_id = isset($_POST['gateway_id']) ? sanitize_key($_POST['gateway_id']) : '';
        if (empty($gateway_id)) {
            wp_send_json_success(['html' => '']);
            return;
        }
        
        $gateway = $this->gateway_manager->get_available_gateways()[$gateway_id] ?? null;
        if (!$gateway) {
            wp_send_json_error();
            return;
        }

        $options = get_option('slr_plugin_options', []);
        
        ob_start();
        echo '<h3>تنظیمات ' . esc_html($gateway->get_name()) . '</h3>';
        
        $ui_helper = new SLR_Admin_UI(SLR_PLUGIN_NAME_FOR_INSTANCE, SLR_PLUGIN_VERSION_FOR_INSTANCE);

        foreach ($gateway->get_settings_fields() as $field_id => $field_args) {
            echo '<div class="setting-option">';
            echo '<label for="'.esc_attr($field_id).'">'. esc_html($field_args['label']) .'</label>';
            $ui_helper->render_setting_field($field_id, $field_args['type'], $options, $field_args['placeholder'] ?? '');
             if (!empty($field_args['desc'])) {
                echo '<p class="description">' . wp_kses_post($field_args['desc']) . '</p>';
            }
            echo '</div>';
        }
        $fields_html = ob_get_clean();

        wp_send_json_success(['html' => $fields_html]);
    }


    public function handle_test_telegram_connection() { 
                check_ajax_referer('yakutlogin_admin_nonce', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Unauthorized access.']);
        }

        $bot_token = isset($_POST['bot_token']) ? sanitize_text_field($_POST['bot_token']) : '';
        if (empty($bot_token)) {
            wp_send_json_error(['message' => 'Bot Token cannot be empty.']);
        }

        require_once SLR_PLUGIN_DIR . 'includes/integrations/class-slr-telegram-handler.php';
        $temp_handler = new SLR_Telegram_Handler($bot_token);
        $response = $temp_handler->get_me();

        if (is_wp_error($response)) {
            wp_send_json_error(['message' => 'Connection failed: ' . $response->get_error_message()]);
        }

        if (isset($response['ok']) && $response['ok'] === true && isset($response['result']['username'])) {
            $bot_username = $response['result']['username'];
            
            $options = get_option('slr_plugin_options', []);
            $options['telegram_bot_username'] = $bot_username;
            update_option('slr_plugin_options', $options);

            wp_send_json_success([
                'message' => 'Connection successful!',
                'bot_name' => esc_html($response['result']['first_name']),
                'bot_username' => esc_html($bot_username),
            ]);
        } else {
            wp_send_json_error(['message' => 'Invalid response from Telegram API.']);
        }
    }


    public function handle_create_cf_worker() {
    check_ajax_referer('yakutlogin_admin_nonce', 'nonce');
    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => 'Unauthorized access.']);
    }

    $cf_account_id = isset($_POST['cf_account_id']) ? sanitize_text_field($_POST['cf_account_id']) : '';
    $cf_api_token = isset($_POST['cf_api_token']) ? sanitize_text_field($_POST['cf_api_token']) : '';

    if (empty($cf_account_id) || !preg_match('/^[a-f0-9]{32}$/', $cf_account_id)) {
        wp_send_json_error(['message' => 'The provided Cloudflare Account ID is invalid.']);
    }

    $options = get_option('slr_plugin_options', []);
    $bot_token = $options['telegram_bot_token'] ?? '';

    if (empty($cf_api_token) || empty($bot_token)) {
        wp_send_json_error(['message' => 'Please fill all fields: Bot Token, Account ID, and API Token.']);
    }

    $worker_name = 'yakutlogin-universal-proxy-' . substr(md5(home_url()), 0, 10);
    $proxy_secret = wp_generate_password(64, false, false);
    $target_webhook_url = get_rest_url(null, 'yakutlogin/v1/telegram-webhook');

    $worker_script = "
        const TELEGRAM_API_HOST = 'api.telegram.org';
        const WP_WEBHOOK_URL = '{$target_webhook_url}';
        const SECRET_HEADER = 'X-Yakut-Proxy-Secret';
        const SECRET_VALUE = '{$proxy_secret}';

        export default {
            async fetch(request, env, ctx) {
                const url = new URL(request.url);

                // ROUTE 1: Outgoing requests FROM WordPress TO Telegram
                // These requests will have a path like /bot<token>/method
                if (url.pathname.startsWith('/bot')) {
                    const telegramApiUrl = 'https://' + TELEGRAM_API_HOST + url.pathname + url.search;
                    const newRequest = new Request(telegramApiUrl, request);
                    return fetch(newRequest);
                }

                // ROUTE 2: Incoming webhooks FROM Telegram TO WordPress
                // This handles all other paths, assuming they are webhooks.
                if (request.method === 'POST') {
                    const newRequest = new Request(WP_WEBHOOK_URL, request);
                    newRequest.headers.set(SECRET_HEADER, SECRET_VALUE);
                    return fetch(newRequest);
                }

                // Default response for other requests (e.g., a GET request to the root)
                return new Response('YakutLogin Universal Proxy is active.', { status: 200 });
            }
        };
    ";
    
    $boundary = '----' . wp_generate_password(24, false);
    $metadata = json_encode(['main_module' => 'worker.js', 'compatibility_date' => gmdate('Y-m-d')]);
    $body = "--{$boundary}\r\n" . "Content-Disposition: form-data; name=\"metadata\"\r\nContent-Type: application/json\r\n\r\n" . $metadata . "\r\n";
    $body .= "--{$boundary}\r\n" . "Content-Disposition: form-data; name=\"worker.js\"; filename=\"worker.js\"\r\nContent-Type: application/javascript+module\r\n\r\n" . $worker_script . "\r\n";
    $body .= "--{$boundary}--\r\n";

    $cf_api_url = "https://api.cloudflare.com/client/v4/accounts/{$cf_account_id}/workers/scripts/{$worker_name}";
    
    $cf_response = wp_remote_request($cf_api_url, [
        'method'  => 'PUT',
        'headers' => ['Authorization' => "Bearer {$cf_api_token}", 'Content-Type'  => "multipart/form-data; boundary={$boundary}"],
        'body'    => $body,
        'timeout' => 30,
    ]);

    if (is_wp_error($cf_response)) {
        wp_send_json_error(['message' => 'Failed to connect to Cloudflare API: ' . $cf_response->get_error_message()]);
    }

    $cf_response_body = json_decode(wp_remote_retrieve_body($cf_response), true);

    if (isset($cf_response_body['success']) && $cf_response_body['success']) {
        $subdomain_url = "https://api.cloudflare.com/client/v4/accounts/{$cf_account_id}/workers/subdomain";
        $subdomain_res = wp_remote_get($subdomain_url, ['headers' => ['Authorization' => "Bearer {$cf_api_token}"]]);
        $subdomain_body = json_decode(wp_remote_retrieve_body($subdomain_res), true);
        $subdomain = $subdomain_body['result']['subdomain'] ?? 'workers.dev';
        $worker_url = "https://{$worker_name}.{$subdomain}.workers.dev";
        
        require_once SLR_PLUGIN_DIR . 'includes/integrations/class-slr-telegram-handler.php';
        $telegram_handler = new SLR_Telegram_Handler();
        $webhook_response = $telegram_handler->set_webhook($worker_url);

        if (is_wp_error($webhook_response)) {
             wp_send_json_error(['message' => 'Worker created, but failed to set Telegram webhook: ' . $webhook_response->get_error_message()]);
        }


        $options['telegram_worker_url'] = $worker_url;
        $options['telegram_cf_proxy_secret'] = $proxy_secret;
        update_option('slr_plugin_options', $options);
        
        wp_send_json_success([
            'message' => 'Universal Cloudflare Worker created and webhook set successfully!',
            'worker_url' => $worker_url,
        ]);
    } else {
        $error_message = $cf_response_body['errors'][0]['message'] ?? 'Unknown Cloudflare API error.';
        wp_send_json_error(['message' => 'Cloudflare API Error: ' . $error_message]);
    }
}

    public function handle_set_telegram_webhook() {
        check_ajax_referer('yakutlogin_admin_nonce', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Unauthorized access.']);
        }
        $webhook_url = get_rest_url(null, 'yakutlogin/v1/telegram-webhook');

        require_once SLR_PLUGIN_DIR . 'includes/integrations/class-slr-telegram-handler.php';
        $handler = new SLR_Telegram_Handler();
        $response = $handler->set_webhook($webhook_url);

        if (is_wp_error($response)) {
            wp_send_json_error(['message' => 'Failed to set webhook: ' . $response->get_error_message()]);
        }
        
        if (isset($response['ok']) && $response['ok']) {
             wp_send_json_success(['message' => $response['description'] ?? 'Webhook was set successfully!']);
        } else {
            wp_send_json_error(['message' => 'An unknown error occurred.']);
        }
    }

    public function handle_get_telegram_webhook_info() {
        check_ajax_referer('yakutlogin_admin_nonce', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Unauthorized access.']);
        }


        $handler = new SLR_Telegram_Handler();
        $response = $handler->get_webhook_info();

        if (is_wp_error($response)) {
            wp_send_json_error(['message' => 'Failed to get webhook info: ' . $response->get_error_message()]);
        } else {
            wp_send_json_success($response);
        }
    }

    public function handle_get_api_keys() {
        check_ajax_referer('yakutlogin_admin_nonce', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error();
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'slr_api_keys';
        $keys = $wpdb->get_results("SELECT id, name, public_key, created_at, last_used, status FROM $table_name ORDER BY id DESC");

        wp_send_json_success($keys);
    }

    public function handle_generate_api_key() {
    check_ajax_referer('yakutlogin_admin_nonce', 'nonce');
    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => 'دسترسی غیرمجاز.']);
    }

    $key_name = isset($_POST['key_name']) ? sanitize_text_field($_POST['key_name']) : '';
    if (empty($key_name)) {
        wp_send_json_error(['message' => 'نام کلید نمی‌تواند خالی باشد.']);
    }

    global $wpdb;
    $table_name = $wpdb->prefix . 'slr_api_keys';

    $public_key = 'ypk_pub_' . wp_generate_password(40, false);
    $secret_key_plain = 'yps_sec_' . wp_generate_password(60, false);
    $secret_key_hash = wp_hash_password($secret_key_plain);

    $inserted = $wpdb->insert($table_name, [
        'user_id'         => get_current_user_id(),
        'name'            => $key_name,
        'public_key'      => $public_key,
        'secret_key_hash' => $secret_key_hash,
        'status'          => 'active',
    ]);

    if ($inserted) {
        wp_send_json_success([
            'message'      => 'کلید با موفقیت ایجاد شد. لطفا کلید مخفی را کپی کنید، زیرا دیگر نمایش داده نخواهد شد.',
            'public_key'   => $public_key,
            'secret_key'   => $secret_key_plain,
        ]);
    } else {
        wp_send_json_error(['message' => 'خطا در ایجاد کلید در دیتابیس.']);
    }
}
        public function handle_revoke_api_key() {
        check_ajax_referer('yakutlogin_admin_nonce', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'دسترسی غیرمجاز.']);
        }

        $key_id = isset($_POST['key_id']) ? absint($_POST['key_id']) : 0;
        if (empty($key_id)) {
            wp_send_json_error(['message' => 'شناسه کلید نامعتبر است.']);
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'slr_api_keys';

        $updated = $wpdb->update(
            $table_name,
            ['status' => 'revoked'],
            ['id' => $key_id],
            ['%s'],
            ['%d']
        );

        if ($updated !== false) {
            wp_send_json_success(['message' => 'کلید با موفقیت باطل شد.']);
        } else {
            wp_send_json_error(['message' => 'خطا در باطل کردن کلید.']);
        }
    }

    public function handle_slr_import_from_digits() {
        check_ajax_referer('yakutlogin_admin_nonce', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Unauthorized access.']);
        }

        $digits_meta_key = isset($_POST['meta_key']) ? sanitize_text_field($_POST['meta_key']) : 'digits_phone_no';
        $is_dry_run = isset($_POST['dry_run']) && $_POST['dry_run'] === 'true';

        $args = [
            'meta_key'     => $digits_meta_key,
            'meta_compare' => 'EXISTS',
            'meta_query'   => [
                [
                    'key'     => 'slr_phone_number',
                    'compare' => 'NOT EXISTS',
                ]
            ],
            'fields' => ['ID', 'display_name', 'user_email'],
        ];
        
        $users_to_import = get_users($args);
        $total_found = count($users_to_import);

        if ($is_dry_run) {
            wp_send_json_success([
                'message' => "تست کامل شد. {$total_found} کاربر برای درون‌ریزی یافت شد که هنوز شماره تلفنی در سیستم یاکوت لاگین ندارند."
            ]);
        }
        
        $imported_count = 0;
        $log_details = [];
        $gateway_manager = new SLR_Gateway_Manager();

        foreach ($users_to_import as $user) {
            $digits_phone = get_user_meta($user->ID, $digits_meta_key, true);
            if (empty($digits_phone)) {
                continue;
            }

            $normalized_phone = $gateway_manager->normalize_iranian_phone($digits_phone);

            if ($normalized_phone) {
                $existing_user_with_phone = get_users(['meta_key' => 'slr_phone_number', 'meta_value' => $normalized_phone, 'fields' => 'ID']);
                if (empty($existing_user_with_phone)) {
                    update_user_meta($user->ID, 'slr_phone_number', $normalized_phone);
                    $imported_count++;
                    $log_details[] = "SUCCESS: User #{$user->ID} ({$user->display_name}) imported with phone {$normalized_phone}.";
                } else {
                     $log_details[] = "SKIPPED: User #{$user->ID} - Phone number {$normalized_phone} already exists for another user.";
                }
            } else {
                $log_details[] = "FAILED: User #{$user->ID} - Could not normalize phone '{$digits_phone}'.";
            }
        }
        
        wp_send_json_success([
            'message' => "درون‌ریزی کامل شد. از مجموع {$total_found} کاربر یافت شده، تعداد {$imported_count} کاربر با موفقیت درون‌ریزی شدند.",
            'log'     => implode("\n", $log_details)
        ]);
    }

        public function handle_slr_import_from_wc() {
        check_ajax_referer('yakutlogin_admin_nonce', 'nonce');
        if (!current_user_can('manage_woocommerce') || !class_exists('WooCommerce')) {
            wp_send_json_error(['message' => 'Unauthorized access or WooCommerce is not active.']);
        }

        $is_dry_run = isset($_POST['dry_run']) && $_POST['dry_run'] === 'true';

        $args = [
            'meta_key'     => 'billing_phone',
            'meta_compare' => 'EXISTS',
            'meta_query'   => [
                [
                    'key'     => 'slr_phone_number',
                    'compare' => 'NOT EXISTS',
                ]
            ],
            'fields' => ['ID', 'display_name'],
        ];
        
        $users_to_import = get_users($args);
        $total_found = count($users_to_import);

        if ($is_dry_run) {
            wp_send_json_success([
                'message' => "تست کامل شد. {$total_found} مشتری برای درون‌ریزی یافت شد که شماره تلفن صورت‌حساب ووکامرس دارند اما شماره یاکوت لاگین ندارند."
            ]);
        }
        
        $imported_count = 0;
        $log_details = [];
        $gateway_manager = new SLR_Gateway_Manager();

        foreach ($users_to_import as $user) {
            $wc_phone = get_user_meta($user->ID, 'billing_phone', true);
            if (empty($wc_phone)) {
                continue;
            }

            $normalized_phone = $gateway_manager->normalize_iranian_phone($wc_phone);

            if ($normalized_phone) {
                $existing_user_with_phone = get_users(['meta_key' => 'slr_phone_number', 'meta_value' => $normalized_phone, 'fields' => 'ID', 'exclude' => [$user->ID]]);
                if (empty($existing_user_with_phone)) {
                    update_user_meta($user->ID, 'slr_phone_number', $normalized_phone);
                    $imported_count++;
                    $log_details[] = "SUCCESS: User #{$user->ID} ({$user->display_name}) imported with phone {$normalized_phone}.";
                } else {
                     $log_details[] = "SKIPPED: User #{$user->ID} - Phone number {$normalized_phone} already exists for another user.";
                }
            } else {
                $log_details[] = "FAILED: User #{$user->ID} - Could not normalize phone '{$wc_phone}'.";
            }
        }
        
        wp_send_json_success([
            'message' => "درون‌ریزی کامل شد. از مجموع {$total_found} مشتری، شماره تلفن {$imported_count} نفر با موفقیت درون‌ریزی شد.",
            'log'     => implode("\n", $log_details)
        ]);
    }

public function handle_cleanup_data() {
    check_ajax_referer('yakutlogin_admin_nonce', 'nonce');
    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => 'دسترسی غیرمجاز']);
    }
    global $wpdb;
    delete_option('slr_plugin_options');
    $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '\_transient\_slr\_%' OR option_name LIKE '\_transient\_timeout\_slr\_%'");

    wp_send_json_success(['message' => 'تمام اطلاعات افزونه با موفقیت پاکسازی شد. صفحه به زودی رفرش می‌شود.']);
}
}