<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class SLR_Theme_Manager {

    private static $instance = null;
    private $themes = [];
    private $themes_loaded = false;

    private function __construct() {}

    public static function get_instance(): SLR_Theme_Manager {
        if ( self::$instance === null ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function load_themes() {
        if ( $this->themes_loaded ) {
            return;
        }

        require_once SLR_PLUGIN_DIR . 'public/themes/interface-slr-theme.php';

        $theme_dir = SLR_PLUGIN_DIR . 'public/themes/';
        $theme_folders = glob( $theme_dir . '*' , GLOB_ONLYDIR );

        if ( empty($theme_folders) ) {
            $this->themes_loaded = true;
            return;
        }

        foreach ( $theme_folders as $folder ) {
            $theme_json_file = $folder . '/theme.json';
            $theme_class_file = $folder . '/theme.php';

            if ( file_exists( $theme_json_file ) && file_exists( $theme_class_file ) ) {
                $theme_data = json_decode( file_get_contents( $theme_json_file ), true );
                
                if ( $theme_data && !empty($theme_data['id']) && !empty($theme_data['php_class']) ) {
                    require_once $theme_class_file;

                    if ( class_exists( $theme_data['php_class'] ) ) {
                        $theme_id = sanitize_key($theme_data['id']);
                        $theme_data['path'] = $folder . '/';
                        $theme_data['url'] = SLR_PLUGIN_URL . 'public/themes/' . basename($folder) . '/';

                        // Instantiate the theme class
                        $this->themes[ $theme_id ] = new $theme_data['php_class']( $theme_data );
                    }
                }
            }
        }
        $this->themes_loaded = true;
    }

    public function get_theme(string $theme_id): ?SLR_Theme {
        $this->load_themes();
        return $this->themes[ $theme_id ] ?? $this->get_default_theme();
    }

    private function get_default_theme(): ?SLR_Theme {
        return $this->themes['default'] ?? null;
    }

    public function get_themes_for_select(): array {
        $this->load_themes();
        $options = [];
        foreach ( $this->themes as $id => $theme_object ) {
            $theme_data = $theme_object->get_theme_data();
            $options[ $id ] = $theme_data['name'] ?? ucfirst($id);
        }
        return $options;
    }
}