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
        return __( 'کاوه‌نگار', 'yakutlogin' );
    }

    public function get_settings_fields() {
        return array(
            'kavenegar_api_key' => array(
                'label' => __( 'کلید API کاوه‌نگار', 'yakutlogin' ),
                'type'  => 'text',
                'desc'  => __( 'کلید API کاوه‌نگار خود را وارد کنید.', 'yakutlogin' )
            ),
            'kavenegar_sender_line' => array(
                'label' => __( 'خط ارسال کننده کاوه‌نگار (اختیاری)', 'yakutlogin' ),
                'type'  => 'text',
                'desc'  => __( 'اگر از سرویس ارسال سریع (Lookup) استفاده نمی‌کنید، شماره خط خود را وارد کنید. برای استفاده از خط پیش‌فرض، خالی بگذارید.', 'yakutlogin' )
            ),
            'kavenegar_use_lookup' => array(
                'label' => __( 'استفاده از سرویس ارسال سریع (Lookup) برای OTP', 'yakutlogin' ),
                'type'  => 'checkbox',
                'desc'  => __( 'توصیه می‌شود. این روش پیامک‌های کد تایید را از طریق قالب‌های از پیش تایید شده، سریع‌تر و با اطمینان بالاتر ارسال می‌کند.', 'yakutlogin' )
            ),
            'kavenegar_lookup_template' => array(
                'label' => __( 'نام قالب ارسال سریع (Lookup) کاوه‌نگار', 'yakutlogin' ),
                'type'  => 'text',
                'desc'  => __( 'نام قالبی که در پنل کاوه‌نگار برای ارسال کد تایید ثبت کرده‌اید (مثلا: "myOtpTemplate"). اگر ارسال سریع فعال باشد، این فیلد الزامی است. قالب شما باید یک توکن (کد OTP) را بپذیرد.', 'yakutlogin' )
            ),
        );
    }

    public function send_sms( $phone_number, $message, $otp_code = '' ) {
        if ( empty( $this->api_key ) ) {
            error_log( 'YakutLogin Kavenegar Error: API Key is not set.' );
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
            error_log( 'YakutLogin Kavenegar WP Error: ' . $response->get_error_message() );
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
            error_log( 'YakutLogin Kavenegar API Error: ' . $error_message . ' | Response: ' . $response_body );
            return false;
        }
    }
}