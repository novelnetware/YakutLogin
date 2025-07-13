<?php
/**
 * The file that defines the core plugin class.
 * This class orchestrates the loading of dependencies and the initialization of services.
 *
 * @package    Sms_Login_Register
 * @subpackage Sms_Login_Register/includes
 * @since      1.0.0
 */
class Sms_Login_Register {

    protected $loader;
    protected $plugin_name;
    protected $version;
    protected $i18n;

    public function __construct() {
        $this->version = defined('SLR_PLUGIN_VERSION') ? SLR_PLUGIN_VERSION : '1.4.0';
        $this->plugin_name = 'yakutlogin'; // Your plugin's text domain

        $this->load_dependencies();
        $this->set_locale();
        $this->define_admin_hooks();
        $this->define_public_hooks();
    }

    /**
     * Load the required dependencies for this plugin.
     * This function is responsible for including all necessary files.
     */
    private function load_dependencies() {
        // Core Loader
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-sms-login-register-loader.php';
        
        // Admin Classes (Orchestrator and its new specialized handlers)
        require_once plugin_dir_path(dirname(__FILE__)) . 'admin/class-sms-login-register-admin.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'admin/core/class-slr-settings-fields.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'admin/core/class-slr-admin-ui.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'admin/core/class-slr-ajax-handler.php';

        // Public Classes (Orchestrator and its new specialized handlers)
        require_once plugin_dir_path(dirname(__FILE__)) . 'public/class-sms-login-register-public.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'public/core/class-slr-asset-manager.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'public/core/class-slr-user-handler.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'public/core/class-slr-oauth-handler.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'public/core/class-slr-ajax-handler-public.php';

        // Shared Core Handlers
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/core/class-sms-login-register-otp-handler.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/core/class-slr-theme-manager.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/core/class-slr-gateway-manager.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/core/class-slr-captcha-handler.php';
        
        // Integrations
        if (class_exists('WooCommerce')) {
            require_once plugin_dir_path(dirname(__FILE__)) . 'includes/integrations/class-slr-woocommerce-integration.php';
        }
       // require_once plugin_dir_path(dirname(__FILE__)) . 'includes/integrations/class-slr-telegram-handler.php';
       // require_once plugin_dir_path(dirname(__FILE__)) . 'includes/integrations/class-slr-bale-handler.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/integrations/class-slr-bale-otp-handler.php';

        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-sms-login-register-i18n.php';

        $this->loader = new Sms_Login_Register_Loader();
    }

    /**
     * Register all hooks related to the admin area functionality.
     * The new Admin class now handles its own hooks internally. We just instantiate it.
     */
    private function define_admin_hooks() {
        new Sms_Login_Register_Admin($this->get_plugin_name(), $this->get_version());
    }

    /**
     * Register all hooks related to the public-facing functionality.
     * The new Public class now handles its own hooks internally via its services.
     */
    private function define_public_hooks() {
    $public_class = new Sms_Login_Register_Public($this->get_plugin_name(), $this->get_version(), SLR_Theme_Manager::get_instance());
    
    $options = get_option('slr_plugin_options');
    if (class_exists('WooCommerce') && !empty($options['wc_checkout_otp_integration'])) {
        new SLR_WooCommerce_Integration($public_class);
    }
}

private function set_locale() {
    $this->i18n = new Sms_Login_Register_i18n();
    // به جای loader از add_action مستقیم وردپرس استفاده کنید
    add_action('plugins_loaded', array($this->i18n, 'load_plugin_textdomain'));
}

    /**
     * Run the loader to execute all of the hooks with WordPress.
     */
    public function run() {
        $this->loader->run();
    }

    public function get_plugin_name() {
        return $this->plugin_name;
    }

    public function get_version() {
        return $this->version;
    }
}