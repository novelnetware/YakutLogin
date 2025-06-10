<?php
namespace Sms_Login_Register_Elementor;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class SLR_Elementor_Widget_Loader {

    private static $_instance = null;

    public static function instance() {
        if ( is_null( self::$_instance ) ) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    private function __construct() {
        add_action( 'elementor/widgets/register', [ $this, 'register_widgets' ] );
         // For Elementor Pro, if you want to place it in specific categories (like "Theme Elements")
        // add_action( 'elementor/elements/categories_registered', [ $this, 'add_elementor_widget_categories' ] );
    }

    public function register_widgets( $widgets_manager ) {
        require_once SLR_PLUGIN_DIR . 'includes/elementor/widgets/class-slr-otp-form-widget.php';
        $widgets_manager->register( new Widgets\SLR_Otp_Form_Widget() );
    }

    // Optional: Add custom category
    /*
    public function add_elementor_widget_categories( $elements_manager ) {
        $elements_manager->add_category(
            'slr-elements',
            [
                'title' => __( 'SMS Login Register', 'sms-login-register' ),
                'icon' => 'fa fa-plug', // Choose an icon
            ]
        );
    }
    */
}