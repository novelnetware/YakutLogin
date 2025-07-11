<?php
/**
 * A helper class for all user-related operations like finding, creating,
 * and identifying users.
 *
 * @package     Sms_Login_Register
 * @subpackage  Sms_Login_Register/public/core
 * @since       1.4.0
 */
if ( ! defined( 'ABSPATH' ) ) exit;

class SLR_User_Handler {

    /**
     * Determines if an input is an email or a phone number.
     *
     * @param string $input The user input.
     * @return array Containing 'type' (email, phone, invalid) and 'value' (sanitized/normalized).
     */
    public static function determine_identifier_type(string $input): array {
        $sanitized_input = sanitize_text_field(trim($input));
        
        if (is_email($sanitized_input)) {
            return ['type' => 'email', 'value' => $sanitized_input];
        }

        // Use the Gateway Manager to normalize the phone number
        if (class_exists('SLR_Gateway_Manager')) {
            $gateway_manager = new SLR_Gateway_Manager();
            $normalized_phone = $gateway_manager->normalize_iranian_phone($sanitized_input);
            if ($normalized_phone) {
                return ['type' => 'phone', 'value' => $normalized_phone];
            }
        }

        return ['type' => 'invalid', 'value' => null];
    }

    /**
     * Finds a user by identifier (email/phone). If not found, creates a new one.
     *
     * @param string $identifier The normalized email or phone.
     * @param string $id_type    The type of identifier ('email' or 'phone').
     * @return WP_User|WP_Error The user object on success, or a WP_Error object on failure.
     */
    public static function find_or_create_user(string $identifier, string $id_type) {
        $user = null;
        
        if ($id_type === 'email') {
            $user = get_user_by('email', $identifier);
        } elseif ($id_type === 'phone') {
            $users = get_users(['meta_key' => 'slr_phone_number', 'meta_value' => $identifier, 'number' => 1]);
            if (!empty($users)) {
                $user = $users[0];
            }
        }

        // If user is found, return it.
        if ($user) {
            return $user;
        }

        // --- User not found, create a new one ---
        $random_password = wp_generate_password(16, false);
        $user_id = 0;

        if ($id_type === 'email') {
            // Create a unique username based on the email
            $username = 'user_' . substr(md5($identifier), 0, 8);
            if (username_exists($username)) {
                $username = $username . '_' . wp_rand(10,99);
            }
            $user_id = wp_create_user($username, $random_password, $identifier);

        } elseif ($id_type === 'phone') {
            // Create a unique username and a temporary email
            $username = 'user_' . preg_replace('/[^0-9]/', '', $identifier);
            if (username_exists($username)) {
                $username = $username . '_' . wp_rand(10,99);
            }
            $temp_email = $username . '@' . wp_parse_url(home_url(), PHP_URL_HOST);
            
            $user_id = wp_create_user($username, $random_password, $temp_email);
            
            if (!is_wp_error($user_id)) {
                update_user_meta($user_id, 'slr_phone_number', $identifier);
                // A flag to ask the user to update their email later
                update_user_meta($user_id, 'slr_requires_email_update', true);
            }
        }

        if (is_wp_error($user_id)) {
            return new WP_Error('user_creation_failed', $user_id->get_error_message(), ['status' => 500]);
        }
        
        $new_user = get_user_by('id', $user_id);
        
        // Hook for new user registration
        do_action('user_register', $user_id);

        return $new_user;
    }
}