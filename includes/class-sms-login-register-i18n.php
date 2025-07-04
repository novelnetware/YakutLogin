<?php

/**
 * Define the internationalization functionality
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @link       https://yakut.ir/
 * @since      1.0.0
 *
 * @package    Sms_Login_Register
 * @subpackage Sms_Login_Register/includes
 */

/**
 * Define the internationalization functionality.
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @since      1.0.0
 * @package    Sms_Login_Register
 * @subpackage Sms_Login_Register/includes
 * @author     Yakut Co <info@yakut.ir>
 */
class Sms_Login_Register_i18n {

    /**
     * Load the plugin text domain for translation.
     *
     * @since    1.0.0
     */
    public function load_plugin_textdomain() {
        load_plugin_textdomain(
            'yakutlogin', // The plugin's text domain. Must match the Text Domain in the plugin header.
            false, // Deprecated parameter.
            dirname( plugin_basename( SLR_PLUGIN_DIR ) ) . '/languages/' // Path to the .mo file.
        );
    }
}