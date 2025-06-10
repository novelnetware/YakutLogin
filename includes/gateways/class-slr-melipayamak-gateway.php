<?php
/**
 * MeliPayamak SMS Gateway.
 *
 * @link       https://melipayamak.com/
 * @since      1.0.5
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

class SLR_MeliPayamak_Gateway implements SLR_Sms_Gateway {

    private $options;
    private $username;
    private $password;
    private $body_id;

    public function __construct() {
        $this->options = get_option( 'slr_plugin_options', array() );
        $this->username = isset( $this->options['melipayamak_username'] ) ? $this->options['melipayamak_username'] : '';
        $this->password = isset( $this->options['melipayamak_password'] ) ? $this->options['melipayamak_password'] : '';
        $this->body_id = isset( $this->options['melipayamak_body_id'] ) ? $this->options['melipayamak_body_id'] : '';
    }

    /**
     * Returns the unique identifier for the gateway.
     * @return string
     */
    public function get_id() {
        return 'melipayamak';
    }

    /**
     * Returns the display name of the gateway.
     * @return string
     */
    public function get_name() {
        return __( 'ملی پیامک', 'yakutlogin' );
    }

    /**
     * Returns an array of settings fields required by this gateway.
     * @return array
     */
    public function get_settings_fields() {
        return array(
            'melipayamak_username' => array(
                'label' => __( 'نام کاربری ملی پیامک', 'yakutlogin' ),
                'type'  => 'text',
                'desc'  => __( 'نام کاربری شما در سامانه ملی پیامک.', 'yakutlogin' )
            ),
            'melipayamak_password' => array(
                'label' => __( 'کلمه عبور ملی پیامک', 'yakutlogin' ),
                'type'  => 'password',
                'desc'  => __( 'کلمه عبور شما در سامانه ملی پیامک.', 'yakutlogin' )
            ),
            'melipayamak_body_id' => array(
                'label' => __( 'کد متن پیش‌فرض (Body ID)', 'yakutlogin' ),
                'type'  => 'text',
                'desc'  => __( 'کد متن پیش‌فرض که در پنل ملی پیامک برای ارسال کد تایید (خط خدماتی) تعریف کرده‌اید.', 'yakutlogin' )
            ),
        );
    }

    /**
     * Sends an SMS using MeliPayamak SOAP service.
     *
     * @param string $phone_number The recipient's phone number.
     * @param string $message The message content (ignored by SendByBaseNumber).
     * @param string $otp_code The actual OTP code.
     * @return bool True on success, false on failure.
     */
    public function send_sms( $phone_number, $message, $otp_code = '' ) {
        if ( empty( $this->username ) || empty( $this->password ) || empty( $this->body_id ) ) {
            error_log( 'YakutLogin MeliPayamak Error: API credentials are not set.' );
            return false;
        }

        if ( empty( $otp_code ) ) {
            error_log( 'YakutLogin MeliPayamak Error: OTP code is empty.' );
            return false;
        }

        try {
            ini_set("soap.wsdl_cache_enabled", "0");
            $client = new SoapClient("http://api.payamak-panel.com/post/send.asmx?wsdl", array("encoding"=>"UTF-8"));
            
            $data = array(
                "username" => $this->username,
                "password" => $this->password,
                "text"     => array( (string) $otp_code ), // The template variable(s) as an array.
                "to"       => $phone_number,
                "bodyId"   => (int) $this->body_id
            );

            $result = $client->SendByBaseNumber($data)->SendByBaseNumberResult;

            // According to the documentation, a successful send returns a long number (recId).
            if ( is_numeric($result) && strlen($result) > 10 ) {
                return true;
            } else {
                // Log the error code returned by the API for debugging.
                error_log( 'YakutLogin MeliPayamak API Error: Code ' . $result );
                return false;
            }

        } catch ( SoapFault $e ) {
            error_log( 'YakutLogin MeliPayamak SOAP Fault: ' . $e->getMessage() );
            return false;
        }
    }
}