<?php
/**
 * Handles all communication with the Bale Bot API.
 *
 * @package    Sms_Login_Register
 * @subpackage Sms_Login_Register/includes/integrations
 * @since      1.4.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

// Ensure the interface is loaded
require_once __DIR__ . '/interface-slr-bot-handler.php';

class SLR_Bale_Handler implements SLR_Bot_Handler_Interface {

    private $bot_token;
    private const BALE_API_BASE_URL = 'https://tapi.bale.ai/bot';

    public function __construct(string $token = null) {
        if ($token) {
            $this->bot_token = $token;
        } else {
            $options = get_option('slr_plugin_options', []);
            $this->bot_token = !empty($options['bale_bot_token']) ? $options['bale_bot_token'] : null;
        }
    }

    public function is_active(): bool {
        $options = get_option('slr_plugin_options', []);
        return !empty($options['bale_login_enabled']) && !empty($this->bot_token);
    }

    private function send_request(string $method, array $params = []) {
        if (!$this->bot_token) {
            return new WP_Error('bale_error', __('Bale Bot Token is not configured.', 'yakutlogin'));
        }
        
        $api_url = self::BALE_API_BASE_URL . $this->bot_token . '/' . $method;

        $response = wp_remote_post($api_url, [
            'timeout'     => 20,
            'headers'     => ['Content-Type' => 'application/json; charset=utf-8'],
            'body'        => json_encode($params),
        ]);

        if (is_wp_error($response)) {
            return $response;
        }
        
        $http_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        $decoded_body = json_decode($body, true);

        if ($http_code !== 200 || !isset($decoded_body['ok']) || $decoded_body['ok'] !== true) {
            $error_message = $decoded_body['description'] ?? 'Unknown API error.';
            return new WP_Error('api_error', "Error from Bale API: {$error_message}");
        }
        return $decoded_body;
    }

    public function get_me() {
        return $this->send_request('getMe');
    }

    public function send_message($chat_id, string $text) {
        return $this->send_request('sendMessage', ['chat_id' => $chat_id, 'text' => $text]);
    }
    
    public function request_contact_info($chat_id, string $text) {
        $params = [
            'chat_id' => $chat_id,
            'text' => $text,
            'reply_markup' => json_encode([
                'keyboard' => [[['text' => 'ارسال شماره تلفن', 'request_contact' => true]]],
                'resize_keyboard' => true,
                'one_time_keyboard' => true,
            ]),
        ];
        return $this->send_request('sendMessage', $params);
    }

    public function set_webhook(string $webhook_url) {
        return $this->send_request('setWebhook', ['url' => $webhook_url]);
    }

    public function get_webhook_info() {
        return $this->send_request('getWebhookInfo');
    }
}