<?php
/**
 * The admin-specific functionality of the plugin.
 * This class acts as an orchestrator for the admin area, loading dependencies
 * and initializing services.
 *
 * @package     Sms_Login_Register
 * @subpackage  Sms_Login_Register/admin
 */

class Sms_Login_Register_Admin {

    private $plugin_name;
    private $version;

    public function __construct( $plugin_name, $version ) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;

        $this->load_dependencies();
        $this->init_services();
    }

    /**
     * Load the required dependency files for the admin area.
     */
    private function load_dependencies() {
        // Core functional classes for the admin panel
        require_once plugin_dir_path( __FILE__ ) . 'core/class-slr-settings-fields.php';
        require_once plugin_dir_path( __FILE__ ) . 'core/class-slr-admin-ui.php';
        require_once plugin_dir_path( __FILE__ ) . 'core/class-slr-ajax-handler.php';
    }

    /**
     * Initialize the services (classes) that power the admin panel.
     */
    private function init_services() {
        // The UI handler is responsible for menus, pages, and scripts.
        $admin_ui = new SLR_Admin_UI( $this->plugin_name, $this->version );
        $admin_ui->init_hooks();

        // The AJAX handler is responsible for all backend admin operations.
        new SLR_Ajax_Handler();
    }
}