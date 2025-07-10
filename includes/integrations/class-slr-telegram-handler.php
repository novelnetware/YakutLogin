<?php
/**
 * Handles all communication with the Telegram Bot API.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

// Ensure the interface is loaded
require_once __DIR__ . '/interface-slr-bot-handler.php';

class SLR_Telegram_Handler implements SLR_Bot_Handler_Interface {

    private $bot_token;
    private const TELEGRAM_API_BASE_URL = 'https://api.telegram.org/bot';

    public function __construct(string $token = null) {
        if ($token) {
            $this->bot_token = $token;
        } else {
            $options = get_option('slr_plugin_options', []);
            $this->bot_token = !empty($options['telegram_bot_token']) ? $options['telegram_bot_token'] : null;
        }
    }

    public function is_active(): bool {
        $options = get_option('slr_plugin_options', []);
        return !empty($options['telegram_login_enabled']) && !empty($this->bot_token);
    }

    private function send_request(string $method, array $params = []) {
        if (!$this->bot_token) {
            return new WP_Error('telegram_error', __('Telegram Bot Token is not configured.', 'yakutlogin'));
        }
        $options = get_option('slr_plugin_options');
        $use_cf_worker = !empty($options['telegram_use_cf_worker']);
        $worker_url = !empty($options['telegram_worker_url']) ? $options['telegram_worker_url'] : '';
        $base_url = ($use_cf_worker && !empty($worker_url)) ? rtrim($worker_url, '/') : self::TELEGRAM_API_BASE_URL;
        $api_url = $base_url . '/bot' . $this->bot_token . '/' . $method;

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
            $error_message = 'Unknown API error.';
            if (isset($decoded_body['description'])) {
                $error_message = $decoded_body['description'];
            } elseif (!empty($body)) {
                $error_message = "Invalid response (HTTP Code: {$http_code}). Body: " . esc_html(substr($body, 0, 200));
            }
            $error_source = ($use_cf_worker) ? 'Cloudflare Worker' : 'Telegram API';
            return new WP_Error('api_error', "Error from {$error_source}: {$error_message}");
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