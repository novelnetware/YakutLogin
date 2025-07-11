<?php
/**
 * Manages the enqueueing of all public-facing scripts and styles.
 *
 * @package     Sms_Login_Register
 * @subpackage  Sms_Login_Register/public/core
 * @since       1.4.0
 */
if ( ! defined( 'ABSPATH' ) ) exit;

class SLR_Asset_Manager {

    private $plugin_name;
    private $version;
    private $theme_manager;
    private static $scripts_enqueued = false;
    private static $enqueued_themes = [];

    public function __construct($plugin_name, $version, $theme_manager) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
        $this->theme_manager = $theme_manager;
    }

    /**
     * Enqueues the core scripts and styles for the plugin.
     * This function is designed to run only once per page load.
     */
    public function maybe_enqueue_scripts() {
        if (self::$scripts_enqueued) {
            return;
        }

        // Enqueue the main public stylesheet
        wp_enqueue_style(
            $this->plugin_name . '-public-base',
            SLR_PLUGIN_URL . 'public/css/sms-login-register-public.css',
            [],
            $this->version
        );

        // Enqueue the main public JavaScript file
        wp_enqueue_script(
            $this->plugin_name . '-public',
            SLR_PLUGIN_URL . 'public/js/sms-login-register-public.js',
            ['jquery'],
            $this->version,
            true
        );

        // Enqueue captcha scripts if enabled
        $this->enqueue_captcha_scripts_if_needed();

        // Localize script data to pass variables from PHP to JavaScript
        wp_localize_script(
            $this->plugin_name . '-public',
            'slr_public_data',
            [
                'ajax_url'                 => admin_url('admin-ajax.php'),
                'send_otp_nonce'           => wp_create_nonce('slr_send_otp_nonce'),
                'process_form_nonce'       => wp_create_nonce('slr_process_form_nonce'),
                'telegram_request_nonce'   => wp_create_nonce('slr_telegram_request_nonce'),
                'telegram_polling_nonce'   => wp_create_nonce('slr_telegram_polling_nonce'),
                'bale_otp_nonce'           => wp_create_nonce('slr_bale_otp_nonce'),
                'bale_bot_nonce'           => wp_create_nonce('slr_bale_bot_nonce'),
                'bale_polling_nonce'       => wp_create_nonce('slr_bale_polling_nonce'),
                'get_auth_options_nonce'   => wp_create_nonce('yakutlogin_get_auth_options_nonce'),
                'verify_auth_nonce'        => wp_create_nonce('yakutlogin_verify_auth_nonce'),
                'text_sending_otp'         => __('در حال ارسال کد...', 'yakutlogin'),
                'text_processing'          => __('در حال پردازش...', 'yakutlogin'),
                'text_error'               => __('یک خطای غیرمنتظره رخ داد.', 'yakutlogin'),
            ]
        );
        
        self::$scripts_enqueued = true;
    }

    /**
     * Enqueues CAPTCHA scripts if configured in settings.
     */
    public function enqueue_captcha_scripts_if_needed() {
        $options = get_option('slr_plugin_options');
        $captcha_type = $options['captcha_type'] ?? 'none';
        
        $script_handle = '';
        $script_url = '';

        if ($captcha_type === 'recaptcha_v2' && !empty($options['recaptcha_v2_site_key'])) {
            $script_handle = 'google-recaptcha';
            $script_url = 'https://www.google.com/recaptcha/api.js?render=explicit&onload=slrRenderReCaptcha';
        } elseif ($captcha_type === 'turnstile' && !empty($options['turnstile_site_key'])) {
            $script_handle = 'cloudflare-turnstile';
            $script_url = 'https://challenges.cloudflare.com/turnstile/v0/api.js?render=explicit&onload=slrRenderTurnstile';
        }

        if ($script_handle && !wp_script_is($script_handle, 'enqueued')) {
            wp_enqueue_script($script_handle, $script_url, [], null, true);
        }
    }

    /**
     * Enqueues scripts and styles for a specific theme if not already done.
     * @param string $theme_id The ID of the theme to enqueue assets for.
     */
    public function enqueue_theme_assets($theme_id = 'default') {
        if (isset(self::$enqueued_themes[$theme_id])) {
            return; // Already enqueued
        }

        $theme = $this->theme_manager->get_theme($theme_id);
        if (!$theme) {
            $theme = $this->theme_manager->get_theme('default');
            if (!$theme) return;
        }

        $theme_data = $theme->get_theme_data();
        foreach ($theme->get_assets() as $asset) {
            $handle = $asset['handle'] ?? 'slr-asset-' . sanitize_key($theme_id) . '-' . md5($asset['src']);
            $deps = $asset['dependencies'] ?? [];
            if ($asset['type'] === 'style') {
                wp_enqueue_style($handle, $theme_data['url'] . $asset['src'], $deps, $theme_data['version'] ?? $this->version);
            } elseif ($asset['type'] === 'script') {
                wp_enqueue_script($handle, $theme_data['url'] . $asset['src'], $deps, $theme_data['version'] ?? $this->version, true);
            }
        }

        self::$enqueued_themes[$theme_id] = true;
    }
}