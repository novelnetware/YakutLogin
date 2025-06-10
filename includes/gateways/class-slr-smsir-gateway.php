<?php
/**
 * SMS.ir Gateway.
 *
 * @link       https://sms.ir/
 * @since      1.0.8
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

class SLR_SmsIr_Gateway implements SLR_Sms_Gateway {

    private $options;
    private $api_key;
    private $template_id;
    private $otp_parameter_name;

    public function __construct() {
        $this->options = get_option( 'slr_plugin_options', array() );
        $this->api_key = isset( $this->options['smsir_api_key'] ) ? $this->options['smsir_api_key'] : '';
        $this->template_id = isset( $this->options['smsir_template_id'] ) ? $this->options['smsir_template_id'] : '';
        $this->otp_parameter_name = isset( $this->options['smsir_otp_parameter_name'] ) ? $this->options['smsir_otp_parameter_name'] : 'Code'; // Default to 'Code'
    }

    /**
     * Returns the unique identifier for the gateway.
     * @return string
     */
    public function get_id() {
        return 'smsir';
    }

    /**
     * Returns the display name of the gateway.
     * @return string
     */
    public function get_name() {
        return __( 'SMS.ir', 'yakutlogin' );
    }

    /**
     * Returns an array of settings fields required by this gateway.
     * @return array
     */
    public function get_settings_fields() {
        return array(
            'smsir_api_key' => array(
                'label' => __( 'کد دسترسی (API Key)', 'yakutlogin' ),
                'type'  => 'text',
                'desc'  => __( 'مقدار x-api-key شما در پنل SMS.ir.', 'yakutlogin' )
            ),
            'smsir_template_id' => array(
                'label' => __( 'شناسه قالب (Template ID)', 'yakutlogin' ),
                'type'  => 'text',
                'desc'  => __( 'شناسه قالبی که برای ارسال کد تایید در پنل SMS.ir ایجاد کرده‌اید.', 'yakutlogin' )
            ),
            'smsir_otp_parameter_name' => array(
                'label' => __( 'نام پارامتر کد تایید', 'yakutlogin' ),
                'type'  => 'text',
                'desc'  => __( 'نام پارامتری که در قالب برای کد تایید مشخص کرده‌اید (مثلاً: Code).', 'yakutlogin' )
            ),
        );
    }

    /**
     * Sends an SMS using SMS.ir verify API.
     *
     * @param string $phone_number The recipient's phone number.
     * @param string $message The message content (ignored by verify API).
     * @param string $otp_code The actual OTP code.
     * @return bool True on success, false on failure.
     */
    public function send_sms( $phone_number, $message, $otp_code = '' ) {
        if ( empty($this->api_key) || empty($this->template_id) || empty($this->otp_parameter_name) ) {
            error_log( 'YakutLogin SMS.ir Error: API credentials are not fully set.' );
            return false;
        }

        if ( empty( $otp_code ) ) {
            error_log( 'YakutLogin SMS.ir Error: OTP code is empty.' );
            return false;
        }
        
        $url = 'https://api.sms.ir/v1/send/verify';
        
        $parameters = array(
            array(
                "name" => $this->otp_parameter_name,
                "value" => (string) $otp_code,
            )
        );
        
        $body = array(
            'mobile'      => $phone_number,
            'templateId'  => (int) $this->template_id,
            'parameters'  => $parameters,
        );
        
        $headers = array(
            'x-api-key'    => $this->api_key,
            'Content-Type' => 'application/json',
            'Accept'       => 'text/plain',
        );

        $args = array(
            'method'  => 'POST',
            'headers' => $headers,
            'body'    => wp_json_encode( $body ),
            'timeout' => 20,
        );

        $response = wp_remote_post( $url, $args );

        if ( is_wp_error( $response ) ) {
            error_log( 'YakutLogin SMS.ir WP Error: ' . $response->get_error_message() );
            return false;
        }

        $response_body = wp_remote_retrieve_body( $response );
        $result = json_decode( $response_body, true );

        if ( isset( $result['status'] ) && $result['status'] === 1 ) {
            return true;
        } else {
            $error_message = isset( $result['message'] ) ? $result['message'] : 'Unknown SMS.ir API error';
            error_log( 'YakutLogin SMS.ir API Error: ' . $error_message );
            return false;
        }
    }
}