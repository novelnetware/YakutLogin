<?php
/**
 * Defines the structure of all plugin settings fields.
 * This class acts as a single source of truth for all available options,
 * making it easy to add, remove, or modify settings in one central location.
 *
 * @package     Sms_Login_Register
 * @subpackage  Sms_Login_Register/admin/core
 * @since       1.4.0
 */
if ( ! defined( 'ABSPATH' ) ) exit;

class SLR_Settings_Fields {

    /**
     * Returns the complete list of all possible plugin options and their types.
     *
     * @return array
     */
    public static function get_all_fields() {
        return [
            // General & Email
            'email_otp_enabled'             => 'checkbox',
            'otp_email_subject'             => 'text',
            'otp_email_body'                => 'editor',

            // SMS Gateways
            'sms_provider'                  => 'key',
            'sms_provider_backup'           => 'key',
            'sms_otp_template'              => 'text',

            // Kavenegar
            'kavenegar_api_key'             => 'password',
            'kavenegar_sender_line'         => 'text',
            'kavenegar_use_lookup'          => 'checkbox',
            'kavenegar_lookup_template'     => 'text',

            // MeliPayamak
            'melipayamak_username'          => 'text',
            'melipayamak_password'          => 'password',
            'melipayamak_from'              => 'text',
            'melipayamak_body_id'           => 'number',
            'melipayamak_is_shared'         => 'checkbox',

            // Kavan SMS
            'kavansms_api_key'              => 'password',
            'kavansms_otp_id'               => 'text',

            // Faraz SMS
            'farazsms_username'             => 'text',
            'farazsms_password'             => 'password',
            'farazsms_from'                 => 'text',
            'farazsms_pattern_code'         => 'text',
            'farazsms_otp_variable_name'    => 'text',

            // SMS.ir
            'smsir_api_key'                 => 'password',
            'smsir_template_id'             => 'number',
            'smsir_otp_parameter_name'      => 'text',
            'smsir_fast_mode'               => 'checkbox',
            'smsir_line_number'             => 'text',
            
            // Ghasedak SMS
            'ghasedaksms_api_key'           => 'password',
            'ghasedaksms_line_number'       => 'text',
            'ghasedaksms_use_pattern'       => 'checkbox',

            // PayamResan
            'payamresan_username'           => 'text',
            'payamresan_password'           => 'password',
            'payamresan_from'               => 'text',
            'payamresan_use_template'       => 'checkbox',

            // Google Login
            'google_login_enabled'          => 'checkbox',
            'google_client_id'              => 'text',
            'google_client_secret'          => 'password',

            /*
            // Telegram Login
            'telegram_login_enabled'        => 'checkbox',
            'telegram_bot_token'            => 'password',
            'telegram_bot_username'         => 'text',
            'telegram_use_cf_worker'        => 'checkbox',
            'telegram_worker_url'           => 'text',
            'telegram_cf_proxy_secret'      => 'text',
            */

            // Bale Login
            'bale_login_enabled'            => 'checkbox',
            //'bale_login_mode'               => 'key',
           // 'bale_bot_token'                => 'password',
           // 'bale_bot_username'             => 'text',
            'bale_otp_client_id'            => 'text',
            'bale_otp_client_secret'        => 'password',

            // Discord Login
            'discord_login_enabled'         => 'checkbox',
            'discord_client_id'             => 'text',
            'discord_client_secret'         => 'password',
            
            // LinkedIn Login
            'linkedin_login_enabled'        => 'checkbox',
            'linkedin_client_id'            => 'text',
            'linkedin_client_secret'        => 'password',

            // GitHub Login
            'github_login_enabled'          => 'checkbox',
            'github_client_id'              => 'text',
            'github_client_secret'          => 'password',

            // Captcha Settings
            'captcha_type'                  => 'key',
            'recaptcha_v2_site_key'         => 'text',
            'recaptcha_v2_secret_key'       => 'password',
            'turnstile_site_key'            => 'text',
            'turnstile_secret_key'          => 'password',

            // WooCommerce Integration
            'wc_checkout_otp_integration'   => 'checkbox',
        ];
    }
}