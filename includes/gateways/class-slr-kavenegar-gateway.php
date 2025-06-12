<?php
/**
 * Kavenegar SMS Gateway.
 *
 * @link       https://kavenegar.com/
 * @since      1.0.2
 *
 * @package    Sms_Login_Register
 * @subpackage Sms_Login_Register/includes/gateways
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

// Ensure the interface is loaded as a fallback.
if ( ! interface_exists('SLR_Sms_Gateway') ) {
    require_once SLR_PLUGIN_DIR . 'includes/gateways/interface-slr-sms-gateway.php';
}

class SLR_Kavenegar_Gateway implements SLR_Sms_Gateway {

    private $api_key;
    private $options;
    private $sender_line;
    private $use_lookup;
    private $lookup_template_name;


    public function __construct() {
        $this->options = get_option( 'slr_plugin_options', array() );
        $this->api_key = isset( $this->options['kavenegar_api_key'] ) ? $this->options['kavenegar_api_key'] : '';
        $this->sender_line = isset( $this->options['kavenegar_sender_line'] ) ? $this->options['kavenegar_sender_line'] : '';
        $this->use_lookup = !empty( $this->options['kavenegar_use_lookup'] );
        $this->lookup_template_name = isset( $this->options['kavenegar_lookup_template'] ) ? $this->options['kavenegar_lookup_template'] : '';
    }

    public function get_id() {
        return 'kavenegar';
    }

    public function get_name() {
        return __( 'Kavenegar', 'sms-login-register' );
    }

    public function get_settings_fields() {
        return array(
            'kavenegar_api_key' => array(
                'label' => __( 'Kavenegar API Key', 'sms-login-register' ),
                'type'  => 'text',
                'desc'  => __('Enter your Kavenegar API key.', 'sms-login-register')
            ),
            'kavenegar_sender_line' => array(
                'label' => __( 'Kavenegar Sender Line (Optional)', 'sms-login-register' ),
                'type'  => 'text',
                'desc'  => __('Enter your Kavenegar sender line number if you are not using Lookup. Leave empty for default.', 'sms-login-register')
            ),
            'kavenegar_use_lookup' => array(
                'label' => __( 'Use Kavenegar Lookup for OTP', 'sms-login-register' ),
                'type'  => 'checkbox',
                'desc'  => __('Recommended for sending OTPs via pre-approved templates. Faster and more reliable.', 'sms-login-register')
            ),
            'kavenegar_lookup_template' => array(
                'label' => __( 'Kavenegar Lookup Template Name', 'sms-login-register' ),
                'type'  => 'text',
                'desc'  => __('The name of your OTP template registered in Kavenegar (e.g., "myOtpTemplate"). Required if Lookup is enabled. The template should accept one token (your OTP code).', 'sms-login-register')
            ),
        );
    }

    public function send_sms( $phone_number, $message, $otp_code = '' ) {
        if ( empty( $this->api_key ) ) {
            error_log( 'SLR Kavenegar Error: API Key is not set.' );
            return false;
        }

        $url = '';
        $params = array();

        if ( $this->use_lookup && !empty($this->lookup_template_name) && !empty($otp_code) ) {
            $url = sprintf( 'https://api.kavenegar.com/v1/%s/verify/lookup.json', $this->api_key );
            $params = array(
                'receptor' => $phone_number,
                'template' => $this->lookup_template_name,
                'token'    => $otp_code,
            );
        } else {
             if ( empty( $this->sender_line ) ) {
                error_log( 'SLR Kavenegar Error: Sender line is not set for regular SMS.' );
            }
            $url = sprintf( 'https://api.kavenegar.com/v1/%s/sms/send.json', $this->api_key );
            $params = array(
                'receptor' => $phone_number,
                'sender'   => $this->sender_line,
                'message'  => $message,
            );
        }

        $response = wp_remote_post( $url, array(
            'method'    => 'POST',
            'timeout'   => 20,
            'body'      => $params,
        ) );

        if ( is_wp_error( $response ) ) {
            error_log( 'SLR Kavenegar WP Error: ' . $response->get_error_message() );
            return false;
        }

        $response_body = wp_remote_retrieve_body( $response );
        $result = json_decode( $response_body, true );

        if ( isset( $result['return']['status'] ) && ( $result['return']['status'] == 200 ) ) {
            return true;
        } else {
            $error_message = isset( $result['return']['message'] ) ? $result['return']['message'] : 'Unknown Kavenegar API error';
             if (isset($result['entries'][0]['message'])) {
                $error_message = $result['entries'][0]['message'];
            }
            error_log( 'SLR Kavenegar API Error: ' . $error_message . ' | Response: ' . $response_body );
            return false;
        }
    }
}