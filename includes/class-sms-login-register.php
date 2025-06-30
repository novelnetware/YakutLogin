<?php
/**
 * The file that defines the core plugin class
 *
 * @link       https://example.com/
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
     * The theme manager instance.
     * @var SLR_Theme_Manager
     */
    protected $theme_manager;

    /**
     * The WebAuthn handler instance.
     * @var SLR_WebAuthn_Handler
     */
    protected $webauthn_handler;

    /**
     * The SMS Gateway manager.
     * @var SLR_Gateway_Manager
     */
    protected $gateway_manager;

    /**
     * Define the core functionality of the plugin.
     */
    public function __construct() {
        if (defined('SLR_PLUGIN_VERSION')) {
            $this->version = SLR_PLUGIN_VERSION;
        } else {
            $this->version = '1.0.0';
        }
        $this->plugin_name = 'YakutLogin';

        $this->load_dependencies();
        $this->instantiate_handlers();
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
        require_once SLR_PLUGIN_DIR . 'includes/core/class-slr-webauthn-handler.php';

        // Admin and Public classes
        require_once SLR_PLUGIN_DIR . 'admin/class-sms-login-register-admin.php';
        require_once SLR_PLUGIN_DIR . 'public/class-sms-login-register-public.php';

        // Integrations
        if (class_exists('WooCommerce')) {
            require_once SLR_PLUGIN_DIR . 'includes/integrations/class-slr-woocommerce-integration.php';
        }
    }

    /**
     * Instantiate all handlers and controllers.
     * @access private
     */
    private function instantiate_handlers() {
        $this->loader           = new Sms_Login_Register_Loader();
        $this->theme_manager    = SLR_Theme_Manager::get_instance();
        $this->webauthn_handler = new SLR_WebAuthn_Handler();
        $this->gateway_manager  = new SLR_Gateway_Manager();

        // Instantiate admin and public controllers, passing necessary dependencies
        $this->admin  = new Sms_Login_Register_Admin($this->get_plugin_name(), $this->get_version(), $this->webauthn_handler);
        $this->public = new Sms_Login_Register_Public($this->get_plugin_name(), $this->get_version(), $this->theme_manager);
    }
    
    /**
     * Define the locale for this plugin for internationalization.
     * @access   private
     */
    private function set_locale() {
        $plugin_i18n = new Sms_Login_Register_i18n();
        $this->loader->add_action('plugins_loaded', $plugin_i18n, 'load_plugin_textdomain');
    }

    /**
     * Register all of the hooks related to the admin area functionality.
     * @access   private
     */
    private function define_admin_hooks() {
        $plugin_admin = $this->admin;

        $this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_styles');
        $this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts');
        $this->loader->add_action('admin_menu', $plugin_admin, 'add_plugin_admin_menu');
        $this->loader->add_action('admin_init', $plugin_admin, 'register_settings');

        // Register AJAX hooks and point them to the correct handler ($plugin_admin)
        $this->loader->add_action('wp_ajax_yakutlogin_save_settings', $plugin_admin, 'ajax_save_settings');
        $this->loader->add_action('wp_ajax_yakutlogin_get_gateway_fields', $plugin_admin, 'ajax_get_gateway_fields');

        // WebAuthn AJAX hooks for logged-in users in admin
        $this->loader->add_action('wp_ajax_yakutlogin_get_registration_options', $plugin_admin, 'ajax_get_registration_options');
        $this->loader->add_action( 'wp_ajax_yakutlogin_verify_registration', $plugin_admin, 'ajax_verify_registration' );
    }

    /**
     * Register all of the hooks related to the public-facing functionality.
     * @access   private
     */
    private function define_public_hooks() {
        $plugin_public = $this->public;

        $this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'maybe_enqueue_assets', 99);
        
        // AJAX handler for sending OTP (for both guests and logged-in users)
        $this->loader->add_action('wp_ajax_nopriv_slr_send_otp', $plugin_public, 'ajax_send_otp');
        $this->loader->add_action('wp_ajax_slr_send_otp', $plugin_public, 'ajax_send_otp');

        // AJAX handler for processing the generic OTP form
        $this->loader->add_action('wp_ajax_nopriv_slr_process_login_register_otp', $plugin_public, 'ajax_process_login_register_otp');
        $this->loader->add_action('wp_ajax_slr_process_login_register_otp', $plugin_public, 'ajax_process_login_register_otp');
        
        // WebAuthn AJAX hooks for guests (login)
        $this->loader->add_action('wp_ajax_nopriv_yakutlogin_get_authentication_options', $plugin_public, 'ajax_get_authentication_options');
        $this->loader->add_action('wp_ajax_nopriv_yakutlogin_verify_authentication', $plugin_public, 'ajax_verify_authentication');

        // Hooks for wp-login.php forms
        $this->loader->add_action('login_form', $plugin_public, 'add_otp_fields_to_login_form');
        $this->loader->add_action('register_form', $plugin_public, 'add_otp_fields_to_register_form');
        $this->loader->add_filter('authenticate', $plugin_public, 'authenticate_with_otp', 20, 3);
        $this->loader->add_filter('registration_errors', $plugin_public, 'validate_registration_with_otp', 10, 3);
        $this->loader->add_action('user_register', $plugin_public, 'save_pending_phone_number_on_registration', 10, 1);
        
        // Hooks for Google Login
        $this->loader->add_action('init', $plugin_public, 'init_google_login', 5);
        $this->loader->add_action('init', $plugin_public, 'handle_google_callback', 5);

        // Register Shortcode
        add_shortcode('slr_otp_form', [$plugin_public, 'render_slr_otp_form_shortcode']);
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