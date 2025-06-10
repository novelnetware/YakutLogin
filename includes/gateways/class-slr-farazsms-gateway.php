<?php
/**
 * Faraz SMS Gateway.
 *
 * @link       https://farazsms.com/
 * @since      1.0.7
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

class SLR_FarazSms_Gateway implements SLR_Sms_Gateway {

    private $options;
    private $username;
    private $password;
    private $from;
    private $pattern_code;
    private $otp_variable_name;


    public function __construct() {
        $this->options = get_option( 'slr_plugin_options', array() );
        $this->username = isset( $this->options['farazsms_username'] ) ? $this->options['farazsms_username'] : '';
        $this->password = isset( $this->options['farazsms_password'] ) ? $this->options['farazsms_password'] : '';
        $this->from = isset( $this->options['farazsms_from'] ) ? $this->options['farazsms_from'] : '';
        $this->pattern_code = isset( $this->options['farazsms_pattern_code'] ) ? $this->options['farazsms_pattern_code'] : '';
        $this->otp_variable_name = isset( $this->options['farazsms_otp_variable_name'] ) ? $this->options['farazsms_otp_variable_name'] : 'code'; // default to 'code'
    }

    /**
     * Returns the unique identifier for the gateway.
     * @return string
     */
    public function get_id() {
        return 'farazsms';
    }

    /**
     * Returns the display name of the gateway.
     * @return string
     */
    public function get_name() {
        return __( 'فراز اس ام اس', 'yakutlogin' );
    }

    /**
     * Returns an array of settings fields required by this gateway.
     * @return array
     */
    public function get_settings_fields() {
        return array(
            'farazsms_username' => array(
                'label' => __( 'نام کاربری فراز اس‌ام‌اس', 'yakutlogin' ),
                'type'  => 'text',
                'desc'  => __( 'نام کاربری شما در سامانه فراز اس‌ام‌اس.', 'yakutlogin' )
            ),
            'farazsms_password' => array(
                'label' => __( 'کلمه عبور فراز اس‌ام‌اس', 'yakutlogin' ),
                'type'  => 'password',
                'desc'  => __( 'کلمه عبور شما در سامانه فراز اس‌ام‌اس.', 'yakutlogin' )
            ),
             'farazsms_from' => array(
                'label' => __( 'شماره ارسال‌کننده', 'yakutlogin' ),
                'type'  => 'text',
                'desc'  => __( 'شماره خطی که با آن پیامک ارسال می‌شود.', 'yakutlogin' )
            ),
            'farazsms_pattern_code' => array(
                'label' => __( 'کد پترن', 'yakutlogin' ),
                'type'  => 'text',
                'desc'  => __( 'کد الگوی پیامکی که برای ارسال کد تایید ساخته‌اید.', 'yakutlogin' )
            ),
            'farazsms_otp_variable_name' => array(
                'label' => __( 'نام متغیر کد تایید در پترن', 'yakutlogin' ),
                'type'  => 'text',
                'desc'  => __( 'نام متغیری که در الگوی خود برای کد یکبارمصرف تعریف کرده‌اید (مثلا: code یا otp).', 'yakutlogin' )
            ),
        );
    }

    /**
     * Sends an SMS using Faraz SMS pattern API.
     *
     * @param string $phone_number The recipient's phone number.
     * @param string $message The message content (ignored by pattern API).
     * @param string $otp_code The actual OTP code.
     * @return bool True on success, false on failure.
     */
    public function send_sms( $phone_number, $message, $otp_code = '' ) {
        if ( empty($this->username) || empty($this->password) || empty($this->from) || empty($this->pattern_code) || empty($this->otp_variable_name) ) {
            error_log( 'YakutLogin FarazSMS Error: API credentials are not fully set.' );
            return false;
        }

        if ( empty( $otp_code ) ) {
            error_log( 'YakutLogin FarazSMS Error: OTP code is empty.' );
            return false;
        }

        $to = array( $phone_number );
        $input_data = array(
            $this->otp_variable_name => (string) $otp_code,
        );
        
        $url = "https://ippanel.com/patterns/pattern?username=" . $this->username . "&password=" . urlencode($this->password) . "&from=" . $this->from . "&to=" . json_encode($to) . "&input_data=" . urlencode(json_encode($input_data)) . "&pattern_code=" . $this->pattern_code;

        $args = array(
            'method'  => 'POST',
            'timeout' => 20,
            'body'    => $input_data, // As per the cURL example that uses CURLOPT_POSTFIELDS
        );

        $response = wp_remote_post( $url, $args );
        
        if ( is_wp_error( $response ) ) {
            error_log( 'YakutLogin FarazSMS WP Error: ' . $response->get_error_message() );
            return false;
        }

        $response_body = wp_remote_retrieve_body( $response );
        
        // According to the documentation, a successful send returns '0'.
        if ( trim($response_body) === '0' ) {
            return true;
        } else {
            error_log( 'YakutLogin FarazSMS API Error: ' . $response_body );
            return false;
        }
    }
}