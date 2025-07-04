<?php
/**
 * Handles CAPTCHA Verification.
 *
 * @link       https://yakut.ir/
 * @since      1.0.3
 *
 * @package    Sms_Login_Register
 * @subpackage Sms_Login_Register/includes/core
 */

class SLR_Captcha_Handler {

    private $options;

    public function __construct() {
        $this->options = get_option( 'slr_plugin_options', array() );
    }

    /**
     * Verifies the CAPTCHA response.
     *
     * @param string $captcha_response The response token from the CAPTCHA widget.
     * @return bool True if verification is successful or CAPTCHA is disabled, false otherwise.
     */
    public function verify_captcha( $captcha_response ) {
        $captcha_type = isset( $this->options['captcha_type'] ) ? $this->options['captcha_type'] : 'none';

        if ( 'none' === $captcha_type || empty($captcha_type) ) {
            return true; // No CAPTCHA enabled
        }

        if ( empty( $captcha_response ) ) {
            // error_log('SLR CAPTCHA Error: Empty response token.');
            return false; // Empty response
        }

        switch ( $captcha_type ) {
            case 'recaptcha_v2':
                return $this->verify_recaptcha_v2( $captcha_response );
            case 'turnstile':
                return $this->verify_turnstile( $captcha_response );
            default:
                return true; // Unknown type, assume no verification needed or misconfiguration
        }
    }

    private function verify_recaptcha_v2( $response_token ) {
        $secret_key = isset( $this->options['recaptcha_v2_secret_key'] ) ? $this->options['recaptcha_v2_secret_key'] : '';
        if ( empty( $secret_key ) ) {
            // error_log('SLR reCAPTCHA Error: Secret key not set.');
            return false; // Configuration error
        }

        $verify_url = 'https://www.google.com/recaptcha/api/siteverify';
        $params = [
            'secret'   => $secret_key,
            'response' => $response_token,
            'remoteip' => $_SERVER['REMOTE_ADDR'] ?? '', // Optional
        ];

        $api_response = wp_remote_post( $verify_url, ['body' => $params, 'timeout' => 15] );

        if ( is_wp_error( $api_response ) ) {
            // error_log('SLR reCAPTCHA WP Error: ' . $api_response->get_error_message());
            return false;
        }

        $response_body = wp_remote_retrieve_body( $api_response );
        $result = json_decode( $response_body, true );

        if ( isset( $result['success'] ) && $result['success'] === true ) {
            // Optional: Check hostname, action, score for v3 if implemented
            return true;
        } else {
            // error_log('SLR reCAPTCHA Verification Failed: ' . print_r($result, true));
            return false;
        }
    }

    private function verify_turnstile( $response_token ) {
        $secret_key = isset( $this->options['turnstile_secret_key'] ) ? $this->options['turnstile_secret_key'] : '';
        if ( empty( $secret_key ) ) {
            // error_log('SLR Turnstile Error: Secret key not set.');
            return false; // Configuration error
        }

        $verify_url = 'https://challenges.cloudflare.com/turnstile/v0/siteverify';
        $params = [
            'secret'   => $secret_key,
            'response' => $response_token,
            'remoteip' => $_SERVER['REMOTE_ADDR'] ?? '', // Optional
        ];

        $api_response = wp_remote_post( $verify_url, ['body' => $params, 'timeout' => 15] );

        if ( is_wp_error( $api_response ) ) {
            // error_log('SLR Turnstile WP Error: ' . $api_response->get_error_message());
            return false;
        }

        $response_body = wp_remote_retrieve_body( $api_response );
        $result = json_decode( $response_body, true );

        if ( isset( $result['success'] ) && $result['success'] === true ) {
            return true;
        } else {
            // error_log('SLR Turnstile Verification Failed: ' . print_r($result, true));
            return false;
        }
    }
}