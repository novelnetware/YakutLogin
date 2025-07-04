<?php
/**
 * Handles OTP Generation, Storage, and Verification.
 *
 * @link       https://yakut.ir/
 * @since      1.0.0
 *
 * @package    Sms_Login_Register
 * @subpackage Sms_Login_Register/includes/core
 */

class Sms_Login_Register_Otp_Handler {

    /**
     * Default OTP length.
     * Can be made configurable later via settings.
     */
    const OTP_LENGTH = 6;

    /**
     * Default OTP expiration time in minutes.
     * Can be made configurable later via settings.
     */
    const OTP_EXPIRATION_MINUTES = 5;

    /**
     * Prefix for OTP transient keys.
     */
    const OTP_TRANSIENT_PREFIX = 'slr_otp_';

    /**
 * Generates a cryptographically secure numeric OTP.
 *
 * @since 1.0.0
 * @param int $length The desired length of the OTP.
 * @return string The generated OTP.
 */
public static function generate_otp( $length = self::OTP_LENGTH ) {
    // Check if the length is a positive integer.
    if ( ! is_int( $length ) || $length <= 0 ) {
        $length = self::OTP_LENGTH; // Fallback to default length.
    }

    try {
        // Calculate the range for the random number.
        // For a length of 6, the range is 0 to 999999.
        $min = 0;
        $max = 10**$length - 1;

        // Generate a cryptographically secure random integer.
        $otp_number = random_int( $min, $max );

        // Pad the number with leading zeros if it's shorter than the desired length.
        // For example, if random_int generates 123, str_pad will turn it into "000123".
        return str_pad( (string) $otp_number, $length, '0', STR_PAD_LEFT );

    } catch ( Exception $e ) {
        // If random_int() fails for some reason (rare), fallback to a less secure method
        // or handle the error. For OTP, falling back to a weaker method is not recommended.
        // Here we can use wp_rand() as a WordPress-specific fallback.
        $otp_number = wp_rand( 0, 10**$length - 1 );
        return str_pad( (string) $otp_number, $length, '0', STR_PAD_LEFT );
    }
}

    /**
     * Stores the OTP for a given identifier using WordPress transients.
     *
     * @since 1.0.0
     * @param string $identifier A unique identifier (e.g., phone number, email, or a session hash).
     * @param string $otp The OTP to store.
     * @param int $expiration_minutes The duration in minutes for which the OTP is valid.
     * @return bool True if the transient was set, false otherwise.
     */
    public static function store_otp( $identifier, $otp, $expiration_minutes = self::OTP_EXPIRATION_MINUTES ) {
        $transient_key = self::OTP_TRANSIENT_PREFIX . md5( $identifier );
        // Store OTP and the time it was generated
        $data_to_store = array(
            'otp' => password_hash($otp, PASSWORD_DEFAULT), // Store a hash of the OTP
            'timestamp' => time(),
        );
        return set_transient( $transient_key, $data_to_store, $expiration_minutes * MINUTE_IN_SECONDS );
    }

    /**
     * Retrieves the stored OTP data for a given identifier.
     *
     * @since 1.0.0
     * @param string $identifier A unique identifier.
     * @return array|false The stored OTP data (array with 'otp' hash and 'timestamp') or false if not found/expired.
     */
    public static function get_otp_data( $identifier ) {
        $transient_key = self::OTP_TRANSIENT_PREFIX . md5( $identifier );
        return get_transient( $transient_key );
    }

    /**
     * Verifies the submitted OTP against the stored OTP for a given identifier.
     * Deletes the OTP transient upon successful verification or if it's invalid (but found).
     *
     * @since 1.0.0
     * @param string $identifier A unique identifier.
     * @param string $submitted_otp The OTP submitted by the user.
     * @return bool True if verification is successful, false otherwise.
     */
    public static function verify_otp( $identifier, $submitted_otp ) {
        $transient_key = self::OTP_TRANSIENT_PREFIX . md5( $identifier );
        $stored_data = self::get_otp_data( $identifier );

        if ( false === $stored_data || !isset($stored_data['otp']) || !isset($stored_data['timestamp']) ) {
            return false; // OTP not found or expired
        }

        // Verify the submitted OTP against the stored hash
        if ( password_verify( $submitted_otp, $stored_data['otp'] ) ) {
            // OTP is correct, delete it to prevent reuse
            self::delete_otp( $identifier );
            return true;
        }

        // OTP is incorrect.
        // Optionally, you could implement a counter for failed attempts here.
        // For now, we don't delete on incorrect attempt, allowing retries until expiration.
        // Or, delete it to force a new OTP request:
        // self::delete_otp( $identifier );
        return false;
    }

    /**
     * Deletes the OTP transient for a given identifier.
     *
     * @since 1.0.0
     * @param string $identifier A unique identifier.
     * @return bool True if the transient was deleted, false otherwise.
     */
    public static function delete_otp( $identifier ) {
        $transient_key = self::OTP_TRANSIENT_PREFIX . md5( $identifier );
        return delete_transient( $transient_key );
    }

    /**
     * Checks if an OTP has been sent recently for a given identifier to prevent abuse.
     *
     * @since 1.0.0
     * @param string $identifier A unique identifier.
     * @param int $cooldown_seconds The minimum time in seconds between OTP requests.
     * @return bool True if within cooldown period, false otherwise.
     */
    public static function is_on_cooldown( $identifier, $cooldown_seconds = 60 ) {
        $transient_key = self::OTP_TRANSIENT_PREFIX . md5( $identifier );
        $stored_data = get_transient( $transient_key ); // Check existing OTP data

        if ( false !== $stored_data && isset($stored_data['timestamp']) ) {
            $time_elapsed = time() - $stored_data['timestamp'];
            if ($time_elapsed < $cooldown_seconds) {
                return true; // Still in cooldown
            }
        }
        return false;
    }
}