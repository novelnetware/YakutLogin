<?php
/**
 * Interface for SMS Gateway services.
 *
 * @link       https://example.com/
 * @since      1.0.2
 *
 * @package    Sms_Login_Register
 * @subpackage Sms_Login_Register/includes/gateways
 */

interface SLR_Sms_Gateway {

    /**
     * Returns the unique identifier for the gateway.
     * @return string
     */
    public function get_id();

    /**
     * Returns the display name of the gateway.
     * @return string
     */
    public function get_name();

    /**
     * Sends an SMS.
     *
     * @param string $phone_number The recipient's phone number.
     * @param string $message The message content (can include OTP).
     * @param string $otp_code The actual OTP code, if needed for specific template sending.
     * @return bool True on success, false on failure.
     */
    public function send_sms( $phone_number, $message, $otp_code = '' );

    /**
     * Returns an array of settings fields required by this gateway.
     * These will be displayed on the plugin's settings page.
     * Example: [ 'api_key' => [ 'label' => 'API Key', 'type' => 'text' ], ... ]
     * @return array
     */
    public function get_settings_fields();

    /**
     * Validates and saves the gateway-specific settings.
     * @param array $settings The settings submitted by the user.
     * @return array Sanitized settings to be saved.
     */
    // public function validate_settings( $settings ); // We might add this later if complex validation is needed per gateway
}