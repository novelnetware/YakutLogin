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
     * For simplicity, we'll manually register them here.
     * A more advanced approach might scan a directory or use a filter.
     */
    private function load_gateways() {
        // Manually include and register gateways
        // Kavenegar Example
        if ( file_exists( SLR_PLUGIN_DIR . 'includes/gateways/class-slr-kavenegar-gateway.php' ) ) {
            require_once SLR_PLUGIN_DIR . 'includes/gateways/class-slr-kavenegar-gateway.php';
            if ( class_exists( 'SLR_Kavenegar_Gateway' ) ) {
                $kavenegar = new SLR_Kavenegar_Gateway();
                $this->gateways[$kavenegar->get_id()] = $kavenegar;
            }
        }
        
        // Add other gateways here in the same way
        // e.g., require_once SLR_PLUGIN_DIR . 'includes/gateways/class-slr-melipayamak-gateway.php';
        // $melipayamak = new SLR_Melipayamak_Gateway();
        // $this->gateways[$melipayamak->get_id()] = $melipayamak;

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
            // Log error: No active SMS gateway
            error_log('SLR Error: No active SMS gateway configured or found.');
            return false;
        }

        // Normalize phone number (basic example)
        $phone_number = $this->normalize_iranian_phone($phone_number);
        if (!$phone_number) {
             error_log('SLR Error: Invalid phone number format for SMS OTP.');
            return false;
        }

        // Construct the message. Some gateways might use templates with just the OTP.
        // This can be customized per gateway or made a setting.
        $message_template = isset($this->options['sms_otp_template']) ? $this->options['sms_otp_template'] : __("Your OTP code is: {otp_code}", "sms-login-register");
        $message = str_replace('{otp_code}', $otp_code, $message_template);
        
        // For gateways like Kavenegar that support "Lookup" (template-based OTP sending),
        // they might only need the OTP code and a template name, not the full message.
        // The send_sms method in the gateway class will handle this.
        return $this->active_gateway_instance->send_sms( $phone_number, $message, $otp_code );
    }

    /**
     * Normalizes an Iranian phone number.
     * Converts 09... to +989...
     * (This is a basic normalization, can be improved)
     *
     * @param string $phone
     * @return string|false Normalized phone number or false if invalid pattern.
     */
    public function normalize_iranian_phone( $phone ) {
        $phone = preg_replace( '/[^0-9]/', '', $phone ); // Remove non-numeric characters

        if ( substr( $phone, 0, 3 ) === '989' && strlen( $phone ) === 12 ) { // Already +989... (without +)
            return '+' . $phone;
        } elseif ( substr( $phone, 0, 1 ) === '0' && strlen( $phone ) === 11 && substr( $phone, 0, 2 ) === '09') { // 09...
            return '+98' . substr( $phone, 1 );
        } elseif ( substr( $phone, 0, 2 ) === '98' && strlen( $phone ) === 12 && substr( $phone, 0, 3 ) !== '989') { // 98... (but not 989...) might be landline or incorrect
             // For mobile, it should be 989...
             return false; // Or handle differently
        } elseif (strlen( $phone ) === 10 && substr( $phone, 0, 1 ) === '9') { // 9... (assuming it's Iranian mobile without 0)
            return '+98' . $phone;
        }


        // If it doesn't match common Iranian patterns, or is too short/long
        // This is a very basic validation. A more robust library might be needed for general use.
        if ( !preg_match('/^\+989[0-9]{9}$/', '+98' . ltrim(str_replace('+','',$phone),'0')) && !preg_match('/^09[0-9]{9}$/', $phone) ) {
           // A final check to see if it can be coerced into +989XXXXXXXXX format
           $temp_phone = preg_replace('/[^0-9]/', '', $phone);
           if (substr($temp_phone, 0, 2) == '09' && strlen($temp_phone) == 11) {
               return '+98' . substr($temp_phone, 1);
           }
           if (substr($temp_phone, 0, 1) == '9' && strlen($temp_phone) == 10) { // 9123456789
                return '+98' . $temp_phone;
           }
           if (substr($temp_phone, 0, 3) == '989' && strlen($temp_phone) == 12) { // 989123456789
                return '+' . $temp_phone;
           }
           if (substr($temp_phone, 0, 4) == '+989' && strlen($temp_phone) == 13) { // +989123456789
                return $temp_phone;
           }
           return false; // Not a clearly identifiable Iranian mobile format
        }

        // If already in +989... format
        if (preg_match('/^\+989[0-9]{9}$/', $phone)) {
            return $phone;
        }
        
        return $phone; // Return original if no specific Iranian rule matched but it passed initial regex
    }
}