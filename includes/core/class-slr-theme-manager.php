<?php
/**
 * Manages Form Themes.
 *
 * @package    Sms_Login_Register
 * @subpackage Sms_Login_Register/includes/core
 * @since      1.5.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

class SLR_Theme_Manager {

    private static $instance = null;
    private $themes = array();
    private $themes_loaded = false;

    private function __construct() {
        // Private constructor for singleton pattern
    }

    public static function get_instance() {
        if ( self::$instance === null ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Scans the themes directory and loads theme metadata.
     */
    private function load_themes() {
        if ( $this->themes_loaded ) {
            return;
        }

        $theme_dir = SLR_PLUGIN_DIR . 'public/themes/';
        $theme_folders = glob( $theme_dir . '*' , GLOB_ONLYDIR );
        $default_theme_data = array(
            'default' => array(
                'id' => 'default',
                'name' => __('پیش‌فرض', 'yakutlogin'),
                'path' => SLR_PLUGIN_DIR . 'public/themes/default/',
                'url' => SLR_PLUGIN_URL . 'public/themes/default/',
                'style' => 'style.css',
                'script' => null
            )
        );

        if ( false === $theme_folders ) {
             $this->themes = $default_theme_data;
             $this->themes_loaded = true;
             return;
        }

        foreach ( $theme_folders as $folder ) {
            $theme_json_file = $folder . '/theme.json';
            if ( file_exists( $theme_json_file ) ) {
                $theme_data = json_decode( file_get_contents( $theme_json_file ), true );
                if ( $theme_data && isset( $theme_data['id'] ) && isset( $theme_data['name'] ) ) {
                    $theme_id = sanitize_key( $theme_data['id'] );
                    $this->themes[ $theme_id ] = array(
                        'id'     => $theme_id,
                        'name'   => sanitize_text_field( $theme_data['name'] ),
                        'path'   => $folder . '/',
                        'url'    => SLR_PLUGIN_URL . 'public/themes/' . basename($folder) . '/',
                        'style'  => isset( $theme_data['style'] ) ? sanitize_file_name( $theme_data['style'] ) : null,
                        'script' => isset( $theme_data['script'] ) ? sanitize_file_name( $theme_data['script'] ) : null,
                    );
                }
            }
        }
        
        if (empty($this->themes)) {
            $this->themes = $default_theme_data;
        }

        $this->themes_loaded = true;
    }

    /**
     * Returns an array of available themes.
     *
     * @return array
     */
    public function get_available_themes() {
        $this->load_themes();
        return $this->themes;
    }

    /**
     * Returns the data for a single theme.
     *
     * @param string $theme_id The ID of the theme.
     * @return array|null The theme data or null if not found.
     */
    public function get_theme( $theme_id ) {
        $this->load_themes();
        return isset( $this->themes[ $theme_id ] ) ? $this->themes[ $theme_id ] : null;
    }

    /**
     * Returns themes formatted for a select/options field.
     *
     * @return array
     */
    public function get_themes_for_select() {
        $this->load_themes();
        $options = array();
        foreach ( $this->themes as $id => $theme ) {
            $options[ $id ] = $theme['name'];
        }
        return $options;
    }
}