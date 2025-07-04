<?php
namespace Sms_Login_Register_Elementor;

if ( ! defined( 'ABSPATH' ) ) exit;

class SLR_Elementor_Widget_Loader {

    private static $_instance = null;

    public static function instance() {
        if ( is_null( self::$_instance ) ) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    private function __construct() {
        // فقط ویجت‌ها و دسته‌بندی را ثبت می‌کنیم
        add_action( 'elementor/widgets/register', [ $this, 'register_widgets' ] );
        add_action( 'elementor/elements/categories_registered', [ $this, 'add_elementor_widget_categories' ] );
    }

    public function register_widgets( $widgets_manager ) {
        // ویجت فرم ورود
        require_once SLR_PLUGIN_DIR . 'includes/elementor/widgets/class-slr-otp-form-widget.php';
        $widgets_manager->register( new \Sms_Login_Register_Elementor\Widgets\SLR_Otp_Form_Widget() );

        // ویجت دکمه WebAuthn
        require_once SLR_PLUGIN_DIR . 'includes/elementor/widgets/class-slr-webauthn-widget.php';
        $widgets_manager->register( new \Sms_Login_Register_Elementor\Widgets\SLR_WebAuthn_Widget() );
    }

    public function add_elementor_widget_categories( $elements_manager ) {
        $elements_manager->add_category(
            'slr-elements',
            [
                'title' => __( 'یاکوت لاگین', 'yakutlogin' ),
            ]
        );
    }
}