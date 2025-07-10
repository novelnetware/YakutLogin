<?php
/**
 * The file that defines the core plugin class
 *
 * @link       https://yakut.ir/
 * @since      1.0.0
 * @package    Sms_Login_Register
 * @subpackage Sms_Login_Register/includes
 */

/**
 * The core plugin class.
 */
class Sms_Login_Register {

    /**
     * The loader that's responsible for maintaining and registering all hooks that power the plugin.
     * @var Sms_Login_Register_Loader
     */
    protected $loader;

    /**
     * The unique identifier of this plugin.
     * @var string
     */
    protected $plugin_name;

    /**
     * The current version of the plugin.
     * @var string
     */
    protected $version;

    /**
     * The admin-specific functionality of the plugin.
     * @var Sms_Login_Register_Admin
     */
    protected $admin;

    /**
     * The public-facing functionality of the plugin.
     * @var Sms_Login_Register_Public
     */
    protected $public;

    /**
     * Define the core functionality of the plugin.
     */
    public function __construct() {
        $this->version = defined('SLR_PLUGIN_VERSION') ? SLR_PLUGIN_VERSION : '1.3.0';
        $this->plugin_name = 'YakutLogin';

        $this->load_dependencies();
        $this->instantiate_classes();
        $this->set_locale();
        $this->define_admin_hooks();
        $this->define_public_hooks();
        $this->init_integrations();
    }

    /**
     * Load the required dependencies for this plugin.
     * @access   private
     */
    private function load_dependencies() {
        // Core orchestrator
        require_once SLR_PLUGIN_DIR . 'includes/class-sms-login-register-loader.php';
        // Internationalization
        require_once SLR_PLUGIN_DIR . 'includes/class-sms-login-register-i18n.php';
        // Core Handlers
        require_once SLR_PLUGIN_DIR . 'includes/core/class-sms-login-register-otp-handler.php';
        require_once SLR_PLUGIN_DIR . 'includes/core/class-slr-theme-manager.php';
        require_once SLR_PLUGIN_DIR . 'includes/core/class-slr-gateway-manager.php';
        require_once SLR_PLUGIN_DIR . 'includes/core/class-slr-captcha-handler.php';
        require_once SLR_PLUGIN_DIR . 'includes/core/class-slr-rest-api.php';
        
        // Integrations
        require_once SLR_PLUGIN_DIR . 'includes/integrations/class-slr-telegram-handler.php';

        require_once SLR_PLUGIN_DIR . 'includes/integrations/class-slr-telegram-handler.php';
require_once SLR_PLUGIN_DIR . 'includes/integrations/class-slr-bale-handler.php';
require_once SLR_PLUGIN_DIR . 'includes/integrations/class-slr-bale-otp-handler.php';

        if (class_exists('WooCommerce')) {
            require_once SLR_PLUGIN_DIR . 'includes/integrations/class-slr-woocommerce-integration.php';
        }

        // Admin and Public classes
        require_once SLR_PLUGIN_DIR . 'admin/class-sms-login-register-admin.php';
        require_once SLR_PLUGIN_DIR . 'public/class-sms-login-register-public.php';
    }

    /**
     * Instantiate all core classes for the plugin.
     * @access private
     */
    private function instantiate_classes() {
        $this->loader = new Sms_Login_Register_Loader();

        // Instantiate core handlers first as other classes might depend on them
        $theme_manager = SLR_Theme_Manager::get_instance(); // Theme manager is a singleton

        // Instantiate REST API handler
        if (class_exists('SLR_Rest_Api')) {
            new SLR_Rest_Api();
        }

        // Now, instantiate the main admin and public classes
        $this->admin = new Sms_Login_Register_Admin($this->get_plugin_name(), $this->get_version());
        $this->public = new Sms_Login_Register_Public($this->get_plugin_name(), $this->get_version(), $theme_manager);
    }

    /**
     * Define the locale for this plugin for internationalization.
     * @access   private
     */
    private function set_locale() {
        $plugin_i18n = new Sms_Login_Register_i18n();
        $this->loader->add_action('init', $plugin_i18n, 'load_plugin_textdomain');
    }

    /**
     * Register all of the hooks related to the admin area functionality.
     * @access   private
     */
    private function define_admin_hooks() {
        // We use $this->admin directly now that it's guaranteed to be an object.
        $this->loader->add_action('admin_enqueue_scripts', $this->admin, 'enqueue_styles');
        $this->loader->add_action('admin_enqueue_scripts', $this->admin, 'enqueue_scripts');
        $this->loader->add_action('admin_menu', $this->admin, 'add_plugin_admin_menu');
        $this->loader->add_action('admin_init', $this->admin, 'register_settings');

        // AJAX hooks
        $this->loader->add_action('wp_ajax_yakutlogin_save_settings', $this->admin, 'ajax_save_settings');
        $this->loader->add_action('wp_ajax_yakutlogin_get_gateway_fields', $this->admin, 'ajax_get_gateway_fields');
        $this->loader->add_action('wp_ajax_yakutlogin_cleanup_data', $this->admin, 'ajax_cleanup_data');
        $this->loader->add_action('wp_ajax_yakutlogin_test_telegram_connection', $this->admin, 'ajax_test_telegram_connection');
        $this->loader->add_action('wp_ajax_yakutlogin_create_cf_worker', $this->admin, 'ajax_create_cf_worker');
        $this->loader->add_action('wp_ajax_yakutlogin_get_telegram_webhook_info', $this->admin, 'ajax_get_telegram_webhook_info');

        $this->loader->add_action('wp_ajax_yakutlogin_set_telegram_webhook', $this->admin, 'ajax_set_telegram_webhook');

        // START: Add API Key Management AJAX Hooks
        $this->loader->add_action('wp_ajax_yakutlogin_get_api_keys', $this->admin, 'ajax_get_api_keys');
        $this->loader->add_action('wp_ajax_yakutlogin_generate_api_key', $this->admin, 'ajax_generate_api_key');
        $this->loader->add_action('wp_ajax_yakutlogin_revoke_api_key', $this->admin, 'ajax_revoke_api_key');
        // END: Add API Key Management AJAX Hooks

        // this line for the Digits Importer
        $this->loader->add_action('wp_ajax_slr_import_from_digits', $this->admin, 'ajax_slr_import_from_digits');

        // Add this new line for the WooCommerce Importer
        if (class_exists('WooCommerce')) {
            $this->loader->add_action('wp_ajax_slr_import_from_wc', $this->admin, 'ajax_slr_import_from_wc');
        }
        
    }

    /**
     * Register all of the hooks related to the public-facing functionality.
     * @access   private
     */
    private function define_public_hooks() {
        // We use $this->public directly.
        $this->loader->add_action('wp_enqueue_scripts', $this->public, 'maybe_enqueue_scripts', 99);
        
        // OTP AJAX Handlers
        $this->loader->add_action('wp_ajax_nopriv_slr_send_otp', $this->public, 'ajax_send_otp');
        $this->loader->add_action('wp_ajax_slr_send_otp', $this->public, 'ajax_send_otp');
        $this->loader->add_action('wp_ajax_nopriv_slr_process_login_register_otp', $this->public, 'ajax_process_login_register_otp');
        $this->loader->add_action('wp_ajax_slr_process_login_register_otp', $this->public, 'ajax_process_login_register_otp');

        // Telegram AJAX Handlers
        $this->loader->add_action('wp_ajax_nopriv_slr_generate_telegram_request', $this->public, 'ajax_generate_telegram_request');
        $this->loader->add_action('wp_ajax_nopriv_slr_check_telegram_login_status', $this->public, 'ajax_check_telegram_login_status');

        // Bale AJAX Handlers
        $this->loader->add_action('wp_ajax_nopriv_slr_send_bale_otp', $this->public, 'ajax_send_bale_otp');
        $this->loader->add_action('wp_ajax_slr_send_bale_otp', $this->public, 'ajax_send_bale_otp');
        $this->loader->add_action('wp_ajax_nopriv_slr_generate_bale_bot_request', $this->public, 'ajax_generate_bale_bot_request');
        $this->loader->add_action('wp_ajax_nopriv_slr_check_bale_login_status', $this->public, 'ajax_check_bale_login_status');
        
        // Google Login Hooks
        $this->loader->add_action('init', $this->public, 'init_google_login', 5);
        $this->loader->add_action('init', $this->public, 'handle_google_callback', 5);

        // Discord Login Hooks
        $this->loader->add_action('init', $this->public, 'init_discord_login', 5);
        $this->loader->add_action('init', $this->public, 'handle_discord_callback', 5);

        // LinkedIn Login Hooks
        $this->loader->add_action('init', $this->public, 'init_linkedin_login', 5);
        $this->loader->add_action('init', $this->public, 'handle_linkedin_callback', 5);

        // GitHub Login Hooks
        $this->loader->add_action('init', $this->public, 'init_github_login', 5);
        $this->loader->add_action('init', $this->public, 'handle_github_callback', 5);

        // General Hooks
        $this->loader->add_action('wp_mail_failed', $this->public, 'handle_wp_mail_failed', 10, 1);
        add_shortcode('slr_otp_form', [$this->public, 'render_slr_otp_form_shortcode']);
    }

    /**
     * Initialize integrations like WooCommerce.
     * @access private
     */
    private function init_integrations() {
        $options = get_option('slr_plugin_options');
        if (class_exists('WooCommerce') && !empty($options['wc_checkout_otp_integration'])) {
            new SLR_WooCommerce_Integration($this->public);
        }
    }

    /**
     * Run the loader to execute all of the hooks with WordPress.
     */
    public function run() {
        $this->loader->run();
    }

    /**
     * The name of the plugin.
     * @return string The name of the plugin.
     */
    public function get_plugin_name() {
        return $this->plugin_name;
    }

    /**
     * The reference to the class that orchestrates the hooks with the plugin.
     * @return Sms_Login_Register_Loader Orchestrates the hooks of the plugin.
     */
    public function get_loader() {
        return $this->loader;
    }

    /**
     * Retrieve the version number of the plugin.
     * @return string The version number of the plugin.
     */
    public function get_version() {
        return $this->version;
    }
}