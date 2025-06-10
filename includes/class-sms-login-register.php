<?php

/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       https://example.com/
 * @since      1.0.0
 *
 * @package    Sms_Login_Register
 * @subpackage Sms_Login_Register/includes
 */

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    Sms_Login_Register
 * @subpackage Sms_Login_Register/includes
 * @author     Your Name <email@example.com>
 */
class Sms_Login_Register {

    /**
     * The loader that's responsible for maintaining and registering all hooks that power
     * the plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      Sms_Login_Register_Loader    $loader    Maintains and registers all hooks for the plugin.
     */
    protected $loader;

    /**
     * The unique identifier of this plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      string    $plugin_name    The string used to uniquely identify this plugin.
     */
    protected $plugin_name;

    /**
     * The current version of the plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      string    $version    The current version of the plugin.
     */
    protected $version;

    /**
 * The SMS Gateway manager.
 *
 * @since    1.0.2
 * @access   protected
 * @var      SLR_Gateway_Manager    $gateway_manager    Manages SMS gateways.
 */
protected $gateway_manager;
protected $public_handler;

    /**
     * Define the core functionality of the plugin.
     *
     * Set the plugin name and the plugin version that can be used throughout the plugin.
     * Load the dependencies, define the locale, and set the hooks for the admin area and
     * the public-facing side of the site.
     *
     * @since    1.0.0
     */
    public function __construct() {

        $this->public_handler = new Sms_Login_Register_Public( $this->get_plugin_name(), $this->get_version() ); // Store public handler

        if ( defined( 'SLR_PLUGIN_VERSION' ) ) {
            $this->version = SLR_PLUGIN_VERSION;
        } else {
            $this->version = '1.0.0';
        }
        $this->plugin_name = 'sms-login-register';

        $this->gateway_manager = new SLR_Gateway_Manager(); // مقداردهی اولیه
        $this->load_dependencies();
        $this->set_locale();
        $this->define_admin_hooks();
        $this->define_public_hooks();
        $this->init_integrations(); // Call a new method to initialize integrations
    }

    private function init_integrations() {
        $options = get_option( 'slr_plugin_options' );
        if ( class_exists( 'WooCommerce' ) && isset( $options['wc_checkout_otp_integration'] ) && $options['wc_checkout_otp_integration'] ) {
            if ( file_exists( SLR_PLUGIN_DIR . 'includes/integrations/class-slr-woocommerce-integration.php' ) ) {
                // We need to pass the instance of Sms_Login_Register_Public to the integration class
                // if its methods are not static or if it needs to call public methods.
                // Ensure $this->public_handler is instantiated before this.
                 if ($this->public_handler) { // $this->public_handler should be the instance of Sms_Login_Register_Public
                    new SLR_WooCommerce_Integration( $this->public_handler );
                }
            }
        }
    }
    

    /**
 * Retrieve the gateway manager.
 *
 * @since     1.0.2
 * @return    SLR_Gateway_Manager    The gateway manager instance.
 */
public function get_gateway_manager() {
    return $this->gateway_manager;
}

    /**
     * Load the required dependencies for this plugin.
     *
     * Include the following files that make up the plugin:
     *
     * - Sms_Login_Register_Loader. Orchestrates the hooks of the plugin.
     * - Sms_Login_Register_i18n. Defines internationalization functionality.
     * - Sms_Login_Register_Admin. Defines all hooks for the admin area.
     * - Sms_Login_Register_Public. Defines all hooks for the public side of the site.
     *
     * Create an instance of the loader which will be used to register the hooks
     * with WordPress.
     *
     * @since    1.0.0
     * @access   private
     */
    private function load_dependencies() {

        /**
         * The class responsible for orchestrating the actions and filters of the
         * core plugin.
         */
        require_once SLR_PLUGIN_DIR . 'includes/class-sms-login-register-loader.php';

        /**
         * The class responsible for defining internationalization functionality
         * of the plugin.
         */
        require_once SLR_PLUGIN_DIR . 'includes/class-sms-login-register-i18n.php';

         /**
         * The class responsible for handling OTP generation, storage, and verification.
         */
        require_once SLR_PLUGIN_DIR . 'includes/core/class-sms-login-register-otp-handler.php';

        /**
         * The class responsible for defining all actions that occur in the admin area.
         */
        require_once SLR_PLUGIN_DIR . 'admin/class-sms-login-register-admin.php';

        /**
         * The class responsible for defining all actions that occur in the public-facing
         * side of the site.
         */
        require_once SLR_PLUGIN_DIR . 'public/class-sms-login-register-public.php';

        require_once SLR_PLUGIN_DIR . 'includes/core/class-slr-gateway-manager.php';

        require_once SLR_PLUGIN_DIR . 'includes/core/class-slr-captcha-handler.php';

        if ( class_exists( 'WooCommerce' ) ) {
            require_once SLR_PLUGIN_DIR . 'includes/integrations/class-slr-woocommerce-integration.php';
        }

        $this->loader = new Sms_Login_Register_Loader();

    }

    /**
     * Define the locale for this plugin for internationalization.
     *
     * Uses the Sms_Login_Register_i18n class in order to set the domain and to register the hook
     * with WordPress.
     *
     * @since    1.0.0
     * @access   private
     */
    private function set_locale() {
        $plugin_i18n = new Sms_Login_Register_i18n();
        $this->loader->add_action( 'plugins_loaded', $plugin_i18n, 'load_plugin_textdomain' );
    }

    /**
     * Register all of the hooks related to the admin area functionality
     * of the plugin.
     *
     * @since    1.0.0
     * @access   private
     */
    private function define_admin_hooks() {
        $plugin_admin = new Sms_Login_Register_Admin( $this->get_plugin_name(), $this->get_version() );

        $this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_styles' );
        $this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts' );

        // Add menu item
        $this->loader->add_action( 'admin_menu', $plugin_admin, 'add_plugin_admin_menu' );

        // Register settings
        $this->loader->add_action( 'admin_init', $plugin_admin, 'register_settings' );
    }

    /**
     * Register all of the hooks related to the public-facing functionality
     * of the plugin.
     *
     * @since    1.0.0
     * @access   private
     */
    private function define_public_hooks() {
        $plugin_public = $this->public_handler;

        // Enqueue scripts method in Public class now uses a flag, so it's safe to call it
        // from shortcode/widget rendering if needed, or just rely on wp_enqueue_scripts hook.
        // For general availability, let's add it to wp_enqueue_scripts for now and the maybe_enqueue_scripts will handle the rest.
        $this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'maybe_enqueue_scripts' );


        // AJAX handler for sending OTP
        $this->loader->add_action( 'wp_ajax_nopriv_slr_send_otp', $plugin_public, 'ajax_send_otp' );
        $this->loader->add_action( 'wp_ajax_slr_send_otp', $plugin_public, 'ajax_send_otp' ); // For logged-in users too if needed

        // New AJAX handler for processing the generic OTP form
        $this->loader->add_action( 'wp_ajax_nopriv_slr_process_login_register_otp', $plugin_public, 'ajax_process_login_register_otp' );
        $this->loader->add_action( 'wp_ajax_slr_process_login_register_otp', $plugin_public, 'ajax_process_login_register_otp' );


        // --- Hooks for wp-login.php forms (kept for compatibility or specific wp-login.php UX) ---
        $options = get_option( 'slr_plugin_options' );
        if ( isset( $options['email_otp_enabled'] ) && $options['email_otp_enabled'] ) { // Check if email OTP itself is enabled
            // These hooks modify the standard wp-login.php
            $this->loader->add_action( 'login_form', $plugin_public, 'add_otp_fields_to_login_form' );
            $this->loader->add_action( 'register_form', $plugin_public, 'add_otp_fields_to_register_form' );
            
            $this->loader->add_filter( 'authenticate', $plugin_public, 'authenticate_with_otp', 20, 3 );
            $this->loader->add_filter( 'registration_errors', $plugin_public, 'validate_registration_with_otp', 10, 3 );

            $this->loader->add_action( 'init', $plugin_public, 'init_google_login', 5 ); // Early hook for redirect
            $this->loader->add_action( 'init', $plugin_public, 'handle_google_callback', 5 ); // Early hook for callback

            

            
        }
        // --- End wp-login.php hooks ---

        // Register Shortcode
        $this->loader->add_shortcode( 'slr_otp_form', $plugin_public, 'render_slr_otp_form_shortcode' );

        // Hook to save phone number after registration via wp-login.php OTP (if phone was used)
        $this->loader->add_action( 'user_register', $plugin_public, 'save_pending_phone_number_on_registration', 10, 1 );
    }

    /**
     * Run the loader to execute all of the hooks with WordPress.
     *
     * @since    1.0.0
     */
    public function run() {
        $this->loader->run();
    }

    /**
     * The name of the plugin used to uniquely identify it within the context of
     * WordPress and to define internationalization functionality.
     *
     * @since     1.0.0
     * @return    string    The name of the plugin.
     */
    public function get_plugin_name() {
        return $this->plugin_name;
    }

    /**
     * The reference to the class that orchestrates the hooks with the plugin.
     *
     * @since     1.0.0
     * @return    Sms_Login_Register_Loader    Orchestrates the hooks of the plugin.
     */
    public function get_loader() {
        return $this->loader;
    }

    /**
     * Retrieve the version number of the plugin.
     *
     * @since     1.0.0
     * @return    string    The version number of the plugin.
     */
    public function get_version() {
        return $this->version;
    }

    private static $instance;

public static function get_instance() {
    if (null === self::$instance) {
        // This assumes `run_sms_login_register` creates the instance and it can be accessed.
        // This is a simplified approach. A proper singleton pattern might be better.
        // For now, let's assume the `run_sms_login_register` function will set this.
    }
    return self::$instance;
}
// در تابع run_sms_login_register فایل اصلی:
// function run_sms_login_register() {
//    Sms_Login_Register::set_instance(new Sms_Login_Register()); // requires set_instance method
//    Sms_Login_Register::get_instance()->run();
// }
// OR, simpler:
// function run_sms_login_register() {
//    $GLOBALS['slr_main_plugin'] = new Sms_Login_Register();
//    $GLOBALS['slr_main_plugin']->run();
// }
// And a helper:
// function slr_get_main_instance() { return $GLOBALS['slr_main_plugin'] ?? null; }
}