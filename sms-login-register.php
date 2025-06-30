<?php
/**
 * Plugin Name:       YakutLogin
 * Plugin URI:        https://yakut.ir/plugins/yakutlogin/
 * Description:       Enables SMS-based login and registration with OTP, email OTP, Google Login, and CAPTCHA support.
 * Version:           1.9.1
 * Author:            Yakut
 * Author URI:        https://yakut.ir/
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       yakutlogin
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

/**
 * Define constants
 */
define( 'SLR_PLUGIN_VERSION', '1.9.1' ); // SLR for SMS Login Register
require_once plugin_dir_path( __FILE__ ) . 'vendor/autoload.php';
define( 'SLR_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'SLR_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'SLR_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
// Constants for Elementor widget temporary instantiation
define( 'SLR_PLUGIN_NAME_FOR_INSTANCE', 'sms-login-register' );
define( 'SLR_PLUGIN_VERSION_FOR_INSTANCE', SLR_PLUGIN_VERSION );



/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require SLR_PLUGIN_DIR . 'includes/class-sms-login-register.php';


/**
 * Activation hook.
 * Creates all options, roles, tables, and directories for the plugin.
 *
 * @since    1.0.0
 */
function slr_activate_plugin() {
    // Actions to perform on plugin activation, like setting default options.
    if ( ! get_option( 'slr_plugin_options' ) ) {
        $default_options = array(
            'sms_provider' => '',
            'email_otp_enabled' => true,
            'otp_email_subject' => __( 'کد یکبارمصرف شما', 'yakutlogin' ),
            'otp_email_body' => __( "کد تایید شما: {otp_code}\nاین کد تا ۵ دقیقه دیگر معتبر است. \nسایت: {site_title} ({site_url})", 'yakutlogin' ),
            'google_login_enabled' => false,
            'google_client_id' => '',
            'google_client_secret' => '',
            'captcha_type' => 'none',
            'recaptcha_v2_site_key' => '',
            'recaptcha_v2_secret_key' => '',
            'turnstile_site_key' => '',
            'turnstile_secret_key' => '',
            'wc_checkout_otp_integration' => false,
        );
        update_option( 'slr_plugin_options', $default_options );
    }
     // ایجاد جدول برای کلیدهای WebAuthn
    global $wpdb;
    $table_name = $wpdb->prefix . 'slr_webauthn_credentials';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        user_id BIGINT(20) UNSIGNED NOT NULL,
        credential_id VARBINARY(255) NOT NULL,
        public_key TEXT NOT NULL,
        signature_counter BIGINT(20) UNSIGNED NOT NULL,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY credential_id (credential_id(100)),
        KEY user_id (user_id)
    ) $charset_collate;";

    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
    dbDelta( $sql );
}
register_activation_hook( __FILE__, 'slr_activate_plugin' );

/**
 * Deactivation hook.
 * Removes all options, roles, tables, and directories for the plugin.
 *
 * @since    1.0.0
 */
function slr_deactivate_plugin() {
    // Actions to perform on plugin deactivation.
    // We leave this empty for now, as we don't want to delete settings on deactivation.
}
register_deactivation_hook( __FILE__, 'slr_deactivate_plugin' );

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_sms_login_register() {
    $plugin = new Sms_Login_Register();
    $plugin->run();
}
run_sms_login_register();


/**
 * Register Elementor Widgets.
 * This function ensures that Elementor is loaded before attempting to register the widget.
 */
function slr_register_elementor_widgets() {
    // Check if Elementor is loaded and functional
    if ( did_action( 'elementor/loaded' ) ) {
        require_once SLR_PLUGIN_DIR . 'includes/elementor/class-slr-elementor-widget-loader.php';
        \Sms_Login_Register_Elementor\SLR_Elementor_Widget_Loader::instance();
    }
}
add_action( 'plugins_loaded', 'slr_register_elementor_widgets', 99 );

?>