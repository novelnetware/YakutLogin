<?php
namespace Sms_Login_Register_Elementor;

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
        // بررسی فعال بودن المنتور
        if ( ! $this->is_elementor_active() ) {
            return;
        }

        // ثبت هوک‌ها
        add_action( 'elementor/widgets/register', [ $this, 'register_widgets' ] );
        add_action( 'elementor/elements/categories_registered', [ $this, 'add_elementor_widget_categories' ] );
        
    }

    /**
     * بررسی فعال بودن المنتور
     */
    private function is_elementor_active() {
        return did_action( 'elementor/loaded' );
    }

    /**
     * ثبت ویجت‌ها
     */
    public function register_widgets( $widgets_manager ) {
        // بررسی وجود فایل ویجت
        $widget_file = SLR_PLUGIN_DIR . 'includes/elementor/widgets/class-slr-otp-form-widget.php';
        
        if ( ! file_exists( $widget_file ) ) {
            // لاگ خطا
            error_log( 'SLR Elementor Widget: Widget file not found - ' . $widget_file );
            return;
        }

        // بارگذاری فایل ویجت
        require_once $widget_file;

        // بررسی وجود کلاس
        if ( ! class_exists( 'Sms_Login_Register_Elementor\Widgets\SLR_Otp_Form_Widget' ) ) {
            error_log( 'SLR Elementor Widget: Widget class not found' );
            return;
        }

        // ثبت ویجت
        try {
            $widget_instance = new \Sms_Login_Register_Elementor\Widgets\SLR_Otp_Form_Widget();
            $widgets_manager->register( $widget_instance );
        } catch ( Exception $e ) {
            error_log( 'SLR Elementor Widget: Error registering widget - ' . $e->getMessage() );
        }
    }

    /**
     * اضافه کردن دسته‌بندی ویجت‌ها
     */
    public function add_elementor_widget_categories( $elements_manager ) {
        $elements_manager->add_category(
            'slr-elements',
            [
                'title' => __( 'یاکوت لاگین', 'yakutlogin' ),
                'icon' => 'eicon-lock-user', // آیکون مناسب‌تر
            ]
        );
    }

}