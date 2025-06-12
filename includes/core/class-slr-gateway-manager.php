<?php
/**
 * Manages SMS Gateways.
 *
 * @link       https://example.com/
 * @since      1.0.2
 *
 * @package    Sms_Login_Register
 * @subpackage Sms_Login_Register/includes/core
 */

class SLR_Gateway_Manager {

    private $gateways = array();
    private $active_gateway_id = null;
    private $active_gateway_instance = null;
    private $options = array();

    public function __construct() {
        $this->options = get_option( 'slr_plugin_options', array() );
        $this->load_gateways();
        $this->set_active_gateway();
    }

    /**
     * Loads available gateway classes.
     * It's crucial to load the interface before the classes that implement it.
     */
    private function load_gateways() {
        // Load the interface first, so all gateways can implement it.
        require_once SLR_PLUGIN_DIR . 'includes/gateways/interface-slr-sms-gateway.php';
    
        // Now, load the individual gateway classes.
        // Kavenegar
        if ( file_exists( SLR_PLUGIN_DIR . 'includes/gateways/class-slr-kavenegar-gateway.php' ) ) {
            require_once SLR_PLUGIN_DIR . 'includes/gateways/class-slr-kavenegar-gateway.php';
            if ( class_exists( 'SLR_Kavenegar_Gateway' ) ) {
                $kavenegar = new SLR_Kavenegar_Gateway();
                $this->gateways[$kavenegar->get_id()] = $kavenegar;
            }
        }
    
        // MeliPayamak
        if ( file_exists( SLR_PLUGIN_DIR . 'includes/gateways/class-slr-melipayamak-gateway.php' ) ) {
            require_once SLR_PLUGIN_DIR . 'includes/gateways/class-slr-melipayamak-gateway.php';
            if ( class_exists( 'SLR_MeliPayamak_Gateway' ) ) {
                $melipayamak = new SLR_MeliPayamak_Gateway();
                $this->gateways[$melipayamak->get_id()] = $melipayamak;
            }
        }
    
        // Kavan SMS
        if ( file_exists( SLR_PLUGIN_DIR . 'includes/gateways/class-slr-kavansms-gateway.php' ) ) {
            require_once SLR_PLUGIN_DIR . 'includes/gateways/class-slr-kavansms-gateway.php';
            if ( class_exists( 'SLR_KavanSms_Gateway' ) ) {
                $kavansms = new SLR_KavanSms_Gateway();
                $this->gateways[$kavansms->get_id()] = $kavansms;
            }
        }
    
        // Faraz SMS
        if ( file_exists( SLR_PLUGIN_DIR . 'includes/gateways/class-slr-farazsms-gateway.php' ) ) {
            require_once SLR_PLUGIN_DIR . 'includes/gateways/class-slr-farazsms-gateway.php';
            if ( class_exists( 'SLR_FarazSms_Gateway' ) ) {
                $farazsms = new SLR_FarazSms_Gateway();
                $this->gateways[$farazsms->get_id()] = $farazsms;
            }
        }
    
        // SMS.ir
        if ( file_exists( SLR_PLUGIN_DIR . 'includes/gateways/class-slr-smsir-gateway.php' ) ) {
            require_once SLR_PLUGIN_DIR . 'includes/gateways/class-slr-smsir-gateway.php';
            if ( class_exists( 'SLR_SmsIr_Gateway' ) ) {
                $smsir = new SLR_SmsIr_Gateway();
                $this->gateways[$smsir->get_id()] = $smsir;
            }
        }
    
        $this->gateways = apply_filters( 'slr_register_sms_gateways', $this->gateways );
    }

    /**
     * Sets the active gateway based on plugin settings.
     */
    private function set_active_gateway() {
        $this->active_gateway_id = isset( $this->options['sms_provider'] ) ? $this->options['sms_provider'] : null;
        if ( $this->active_gateway_id && isset( $this->gateways[$this->active_gateway_id] ) ) {
            $this->active_gateway_instance = $this->gateways[$this->active_gateway_id];
        }
    }

    /**
     * Returns an array of all available (loaded) gateways.
     * @return SLR_Sms_Gateway[]
     */
    public function get_available_gateways() {
        return $this->gateways;
    }

    /**
     * Returns the currently active gateway instance.
     * @return SLR_Sms_Gateway|null
     */
    public function get_active_gateway() {
        return $this->active_gateway_instance;
    }
    
    /**
     * Returns the ID of the currently active gateway.
     * @return string|null
     */
    public function get_active_gateway_id() {
        return $this->active_gateway_id;
    }

    /**
     * Sends an SMS using the active gateway.
     *
     * @param string $phone_number The recipient's phone number.
     * @param string $otp_code The OTP code.
     * @return bool True on success, false on failure.
     */
    public function send_otp( $phone_number, $otp_code ) {
        if ( ! $this->active_gateway_instance ) {
            error_log('SLR Error: No active SMS gateway configured or found.');
            return false;
        }

        $phone_number = $this->normalize_iranian_phone($phone_number);
        if (!$phone_number) {
             error_log('SLR Error: Invalid phone number format for SMS OTP.');
            return false;
        }

        $message_template = isset($this->options['sms_otp_template']) ? $this->options['sms_otp_template'] : __("Your OTP code is: {otp_code}", "sms-login-register");
        $message = str_replace('{otp_code}', $otp_code, $message_template);
        
        return $this->active_gateway_instance->send_sms( $phone_number, $message, $otp_code );
    }

    /**
     * Normalizes an Iranian phone number.
     *
     * @param string $phone
     * @return string|false Normalized phone number or false if invalid pattern.
     */
    public function normalize_iranian_phone( $phone ) {
        $phone = preg_replace( '/[^0-9]/', '', $phone );

        if ( substr( $phone, 0, 1 ) === '0' && strlen( $phone ) === 11 && substr( $phone, 0, 2 ) === '09') {
            return '+98' . substr( $phone, 1 );
        }
        if (strlen( $phone ) === 10 && substr( $phone, 0, 1 ) === '9') {
            return '+98' . $phone;
        }
        if (substr( $phone, 0, 3 ) === '989' && strlen( $phone ) === 12 ) {
            return '+' . $phone;
        }
        if (substr( $phone, 0, 4) === '+989' && strlen( $phone ) === 13) {
            return $phone;
        }
        
        return false;
    }
}