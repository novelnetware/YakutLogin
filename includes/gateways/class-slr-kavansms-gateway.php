<?php
/**
 * Kavan SMS Gateway.
 *
 * @link       https://kavansms.com/
 * @since      1.0.6
 *
 * @package    Sms_Login_Register
 * @subpackage Sms_Login_Register/includes/gateways
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

// Ensure the interface is loaded
if ( ! interface_exists('SLR_Sms_Gateway') ) {
    require_once SLR_PLUGIN_DIR . 'includes/gateways/interface-slr-sms-gateway.php';
}

class SLR_KavanSms_Gateway implements SLR_Sms_Gateway {

    private $options;
    private $api_key;
    private $otp_id;

    public function __construct() {
        $this->options = get_option( 'slr_plugin_options', array() );
        $this->api_key = isset( $this->options['kavansms_api_key'] ) ? $this->options['kavansms_api_key'] : '';
        $this->otp_id = isset( $this->options['kavansms_otp_id'] ) ? $this->options['kavansms_otp_id'] : '';
    }

    /**
     * Returns the unique identifier for the gateway.
     * @return string
     */
    public function get_id() {
        return 'kavansms';
    }

    /**
     * Returns the display name of the gateway.
     * @return string
     */
    public function get_name() {
        return __( 'کاوان اس ام اس', 'yakutlogin' );
    }

    /**
     * Returns an array of settings fields required by this gateway.
     * @return array
     */
    public function get_settings_fields() {
        return array(
            'kavansms_api_key' => array(
                'label' => __( 'کد دسترسی (ApiKey)', 'yakutlogin' ),
                'type'  => 'text',
                'desc'  => __( 'کد ApiKey شما در پنل کاوان اس‌ام‌اس.', 'yakutlogin' )
            ),
            'kavansms_otp_id' => array(
                'label' => __( 'شناسه پترن (OtpId)', 'yakutlogin' ),
                'type'  => 'text',
                'desc'  => __( 'شناسه الگوی پیامکی که برای ارسال کد تایید در پنل کاوان اس‌ام‌اس ثبت کرده‌اید.', 'yakutlogin' )
            ),
        );
    }

    /**
     * Sends an SMS using Kavan SMS REST API.
     *
     * @param string $phone_number The recipient's phone number.
     * @param string $message The message content (ignored by sendpatternmessage).
     * @param string $otp_code The actual OTP code.
     * @return bool True on success, false on failure.
     */
    public function send_sms( $phone_number, $message, $otp_code = '' ) {
        if ( empty( $this->api_key ) || empty( $this->otp_id ) ) {
            error_log( 'YakutLogin KavanSMS Error: API Key or OtpId is not set.' );
            return false;
        }

        if ( empty( $otp_code ) ) {
            error_log( 'YakutLogin KavanSMS Error: OTP code is empty.' );
            return false;
        }
        
        $url = 'https://api.kavansms.com/api/sendpatternmessage';
        
        $body = array(
            'OtpId'        => (int) $this->otp_id,
            'ReplaceToken' => array( (string) $otp_code ),
            'MobileNumber' => $phone_number,
        );
        
        $headers = array(
            'ApiKey'       => $this->api_key,
            'Content-Type' => 'application/json',
        );

        $args = array(
            'method'  => 'POST',
            'headers' => $headers,
            'body'    => wp_json_encode( $body ),
            'timeout' => 20,
        );

        $response = wp_remote_post( $url, $args );

        if ( is_wp_error( $response ) ) {
            error_log( 'YakutLogin KavanSMS WP Error: ' . $response->get_error_message() );
            return false;
        }

        $response_body = wp_remote_retrieve_body( $response );
        $result = json_decode( $response_body, true );

        if ( isset( $result['Success'] ) && $result['Success'] === true ) {
            return true;
        } else {
            $error_message = isset( $result['Message'] ) ? $result['Message'] : 'Unknown KavanSMS API error';
            error_log( 'YakutLogin KavanSMS API Error: ' . $error_message );
            return false;
        }
    }
}