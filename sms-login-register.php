<?php
/**
 * Plugin Name:       YakutLogin
 * Plugin URI:        https://yakut.ir/plugins/yakutlogin/
 * Description:       Enables SMS-based login and registration with OTP, email OTP, Google Login, and CAPTCHA support.
 * Version:           1.0.0
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
define( 'SLR_PLUGIN_VERSION', '1.0.4' ); // SLR for SMS Login Register
define( 'SLR_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'SLR_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'SLR_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
// Constants for Elementor widget temporary instantiation (if using that specific approach)
// It's generally better if the widget can get the public class instance via a more robust method,
// but these constants will make the current Elementor widget code functional.
define( 'SLR_PLUGIN_NAME_FOR_INSTANCE', 'sms-login-register' ); // همان text-domain یا نام یکتای افزونه
define( 'SLR_PLUGIN_VERSION_FOR_INSTANCE', SLR_PLUGIN_VERSION ); // استفاده از نسخه فعلی

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
// We will create this class in the next steps.
// require SLR_PLUGIN_DIR . 'includes/class-sms-login-register.php';

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
    //We will uncomment and implement this later.
}
// run_sms_login_register();

/**
 * Activation hook.
 * Creates all options, roles, tables, and directories for the plugin.
 *
 * @since    1.0.0
 */
function slr_activate_plugin() {
    // Actions to perform on plugin activation, like setting default options.
    // We'll add more here later.
    if ( ! get_option( 'slr_plugin_options' ) ) {
        $default_options = array(
            'sms_provider' => '',
            'email_otp_enabled' => true,
            'otp_email_subject' => __( 'Your One-Time Password', 'sms-login-register' ),
            'otp_email_body' => __( "Your OTP code is: {otp_code}\nThis code is valid for 5 minutes. \nSite: {site_title} ({site_url})", 'sms-login-register' ),
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
    // Consider if you want to remove options on deactivation or offer a "remove all data on uninstall" option.
    // For now, we'll leave it empty.
}
register_deactivation_hook( __FILE__, 'slr_deactivate_plugin' );

/**
 * Uninstall hook.
 * It's good practice to clean up when the plugin is uninstalled.
 *
 * @since    1.0.0
 */
function slr_uninstall_plugin() {
    // Actions to perform on plugin uninstall.
    // Delete options
    delete_option( 'slr_plugin_options' );
    // delete_transient( 'slr_some_transient' ); // Example
    // remove any custom database tables if created
}
// It's better to register the uninstall hook in a separate uninstall.php file
// for security and WordPress best practices. We'll do that later if needed.
// For now, this demonstrates the idea. If you create an uninstall.php file
// in the root of your plugin folder, WordPress will automatically use it.
// ... (کدهای قبلی فایل sms-login-register.php تا قبل از تابع run_sms_login_register)

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require SLR_PLUGIN_DIR . 'includes/class-sms-login-register.php';

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

/**
 * Register Elementor Widgets.
 */
function slr_register_elementor_widgets() {
    // Check if Elementor is loaded and functional
    if ( did_action( 'elementor/loaded' ) ) {
        require_once SLR_PLUGIN_DIR . 'includes/elementor/class-slr-elementor-widget-loader.php';
        \Sms_Login_Register_Elementor\SLR_Elementor_Widget_Loader::instance();
    }
}
add_action( 'plugins_loaded', 'slr_register_elementor_widgets', 99 ); // After Elementor might have loaded

run_sms_login_register();

// ... (کدهای مربوط به activation, deactivation, uninstall hooks)
// More to come...
?>