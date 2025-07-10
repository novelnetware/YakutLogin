<?php
/**
 * Handles sending OTPs via the Bale Safir (commercial) service.
 *
 * @package    Sms_Login_Register
 * @subpackage Sms_Login_Register/includes/integrations
 * @since      1.4.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

class SLR_Bale_Otp_Handler {

    private const API_BASE_URL = 'https://safir.bale.ai/api/v2';
    private $client_id;
    private $client_secret;

    public function __construct() {
        $options = get_option('slr_plugin_options', []);
        $this->client_id = $options['bale_otp_client_id'] ?? null;
        $this->client_secret = $options['bale_otp_client_secret'] ?? null;
    }
    
    private function get_access_token(): ?string {
        $cached_token = get_transient('slr_bale_otp_access_token');
        if ($cached_token) {
            return $cached_token;
        }

        if (empty($this->client_id) || empty($this->client_secret)) {
            return null;
        }

        $auth_url = self::API_BASE_URL . '/auth/token';
        $response = wp_remote_post($auth_url, [
            'headers' => ['Content-Type' => 'application/x-www-form-urlencoded'],
            'body'    => [
                'grant_type'    => 'client_credentials',
                'client_id'     => $this->client_id,
                'client_secret' => $this->client_secret,
                'scope'         => 'read',
            ],
        ]);

        if (is_wp_error($response)) {
            return null;
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);
        if (empty($body['access_token'])) {
            return null;
        }

        $expires_in = isset($body['expires_in']) ? intval($body['expires_in']) - 60 : 3600;
        set_transient('slr_bale_otp_access_token', $body['access_token'], $expires_in);

        return $body['access_token'];
    }

    public function send_otp(string $phone, string $otp_code): bool {
        $access_token = $this->get_access_token();
        if (!$access_token) {
            return false;
        }

        // Bale OTP API requires phone number in 98... format (without +)
        $bale_phone_format = preg_replace('/[^0-9]/', '', $phone);
        if (substr($bale_phone_format, 0, 1) === '0') {
            $bale_phone_format = '98' . substr($bale_phone_format, 1);
        }

        $send_url = self::API_BASE_URL . '/send_otp';
        $response = wp_remote_post($send_url, [
            'headers' => [
                'Authorization' => 'Bearer ' . $access_token,
                'Content-Type'  => 'application/json',
            ],
            'body'    => json_encode([
                'phone' => $bale_phone_format,
                'otp'   => (int)$otp_code,
            ]),
        ]);

        return !is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200;
    }
}