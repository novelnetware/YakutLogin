<?php
namespace Sms_Login_Register_Elementor\Widgets;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

/**
 * Elementor OTP Form Widget.
 *
 * @since 1.0.1
 */
class SLR_Otp_Form_Widget extends Widget_Base {

    public function get_name() {
        return 'slr-otp-form';
    }

    public function get_title() {
        return __( 'فرم ورود و عضویت یاکوت', 'yakutlogin' );
    }

    public function get_icon() {
        // استفاده از آیکون قفل و کاربر از کتابخانه استاندارد المنتور
        return 'eicon-form-horizontal';
    }

    public function get_categories() {
    return [ 'slr-elements' ]; 
}

    public function get_keywords() {
        return [ 'otp', 'sms', 'login', 'register', 'form', 'yakut', 'یاقوت', 'ورود', 'پیامک' ];
    }

    protected function register_controls() {

     // --- تب محتوا: تنظیمات عمومی ---
    $this->start_controls_section(
        'content_section_settings',
        [
            'label' => __( 'تنظیمات عمومی فرم', 'yakutlogin' ),
            'tab' => Controls_Manager::TAB_CONTENT,
        ]
    );

    // دریافت لیست پوسته‌ها
    $theme_manager = \SLR_Theme_Manager::get_instance();
    $available_themes = $theme_manager ? $theme_manager->get_themes_for_select() : ['default' => __('پیش‌فرض', 'yakutlogin')];

    // کنترل انتخاب پوسته
    $this->add_control(
        'form_theme',
        [
            'label' => __( 'پوسته فرم', 'yakutlogin' ),
            'type' => Controls_Manager::SELECT,
            'default' => 'default',
            'options' => $available_themes,
            'description' => 'با تغییر پوسته، تنظیمات مربوط به آن در تب "استایل" ظاهر می‌شود.'
        ]
    );

    // کنترل‌های عمومی دیگر
    $this->add_control(
        'form_layout',
        [
            'label' => __( 'چیدمان فرم', 'yakutlogin' ),
            'type' => Controls_Manager::SELECT,
            'default' => 'default',
            'options' => [
                'default'       => __( 'پیش‌فرض (برچسب بالا)', 'yakutlogin' ),
                'compact'       => __( 'فشرده', 'yakutlogin' ),
                'inline_labels' => __( 'برچسب خطی (placeholder)', 'yakutlogin' ),
            ],
            'separator' => 'before',
        ]
    );

    $this->add_control(
        'show_labels',
        [
            'label' => __( 'نمایش برچسب فیلدها', 'yakutlogin' ),
            'type' => Controls_Manager::SWITCHER,
            'label_on' => __( 'نمایش', 'yakutlogin' ),
            'label_off' => __( 'مخفی', 'yakutlogin' ),
            'return_value' => 'yes',
            'default' => 'yes',
        ]
    );
    
    $this->add_control(
        'redirect_to',
        [
            'label' => __( 'ریدایرکت پس از موفقیت', 'yakutlogin' ),
            'type' => Controls_Manager::URL,
            'placeholder' => admin_url(),
            'show_external' => true,
            'default' => [ 'url' => '' ],
            'description' => __('برای ریدایرکت پیش‌فرض خالی بگذارید.', 'yakutlogin'),
        ]
    );

    $this->add_control(
        'hr_buttons',
        [
            'type' => Controls_Manager::DIVIDER,
        ]
    );

    $this->add_control(
        'text_send_otp',
        [
            'label' => __( 'متن دکمه ارسال کد', 'yakutlogin' ),
            'type' => Controls_Manager::TEXT,
            'default' => __( 'ارسال کد تایید', 'yakutlogin' ),
        ]
    );

    $this->add_control(
        'text_submit',
        [
            'label' => __( 'متن دکمه ثبت‌نام/ورود', 'yakutlogin' ),
            'type' => Controls_Manager::TEXT,
            'default' => __( 'ورود / عضویت با کد تایید', 'yakutlogin' ),
        ]
    );

    $this->add_control(
        'text_google',
        [
            'label' => __( 'متن دکمه ورود با گوگل', 'yakutlogin' ),
            'type' => Controls_Manager::TEXT,
            'default' => __( 'ورود توسط گوگل', 'yakutlogin' ),
        ]
    );

    // *** اصلاح شد: بخش تنظیمات عمومی در اینجا بسته می‌شود ***
    $this->end_controls_section();

    // --- بخش کنترل‌های داینامیک پوسته‌ها ---
    // این حلقه اکنون خارج از بخش قبلی قرار دارد و هر پوسته می‌تواند بخش‌های استایل خود را به صورت مستقل ثبت کند.
    if ($theme_manager) {
        foreach ($available_themes as $id => $name) {
            $theme_object = $theme_manager->get_theme($id);
            if ($theme_object) {
                // ما اینجا آبجکت ویجت ($this) را به تابع پاس می‌دهیم تا پوسته بتواند کنترل‌های خود را به آن اضافه کند.
                // هر پوسته باید کنترل‌های خود را داخل یک section با condition قرار دهد تا فقط زمانی نمایش داده شوند که آن پوسته فعال است.
                $theme_object->register_elementor_controls($this);
            }
        }
    }

        // --- Style Tab ---
        $this->start_controls_section(
            'style_section_container',
            [
                'label' => __( 'کادر فرم', 'yakutlogin' ),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );
        $this->add_responsive_control(
            'container_padding',
            [
                'label' => __( 'پدینگ', 'yakutlogin' ),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => [ 'px', '%', 'em' ],
                'selectors' => [
                    '{{WRAPPER}} .slr-otp-form-container' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );
        $this->add_control(
            'container_bg_color',
            [
                'label' => __( 'رنگ پس‌زمینه', 'yakutlogin' ),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .slr-otp-form-container' => 'background-color: {{VALUE}};',
                ],
            ]
        );
        $this->end_controls_section();
        
        $this->start_controls_section(
            'style_section_labels',
            [
                'label' => __( 'برچسب‌ها', 'yakutlogin' ),
                'tab' => Controls_Manager::TAB_STYLE,
                'condition' => [ 'show_labels' => 'yes' ],
            ]
        );
        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'label_typography',
                'selector' => '{{WRAPPER}} .slr-otp-form-container label',
            ]
        );
        $this->add_control(
            'label_color',
            [
                'label' => __( 'رنگ', 'yakutlogin' ),
                'type' => Controls_Manager::COLOR,
                'selectors' => [ '{{WRAPPER}} .slr-otp-form-container label' => 'color: {{VALUE}};' ],
            ]
        );
        $this->end_controls_section();

        $this->start_controls_section(
            'style_section_fields',
            [
                'label' => __( 'فیلدهای ورودی', 'yakutlogin' ),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );
        $this->add_responsive_control(
            'input_padding',
            [
                'label' => __( 'پدینگ', 'yakutlogin' ),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => [ 'px', 'em' ],
                'selectors' => [
                    '{{WRAPPER}} .slr-otp-form-container .slr-input' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );
        $this->add_control(
            'input_text_color',
            [
                'label' => __( 'رنگ متن', 'yakutlogin' ),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .slr-otp-form-container .slr-input' => 'color: {{VALUE}};',
                    '{{WRAPPER}} .slr-otp-form-container .slr-input::placeholder' => 'color: {{VALUE}}; opacity: 0.7;',
                ],
            ]
        );
         $this->add_control(
            'input_bg_color',
            [
                'label' => __( 'رنگ پس‌زمینه', 'yakutlogin' ),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .slr-otp-form-container .slr-input' => 'background-color: {{VALUE}};',
                ],
            ]
        );
        $this->end_controls_section();

        $this->start_controls_section(
            'style_section_buttons',
            [
                'label' => __( 'دکمه‌ها', 'yakutlogin' ),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );
        $this->add_control(
            'button_bg_color',
            [
                'label' => __( 'رنگ پس‌زمینه', 'yakutlogin' ),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .slr-otp-form-container .slr-button' => 'background-color: {{VALUE}}; border-color: {{VALUE}};',
                ],
            ]
        );
         $this->add_control(
            'button_text_color',
            [
                'label' => __( 'رنگ متن', 'yakutlogin' ),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .slr-otp-form-container .slr-button' => 'color: {{VALUE}};',
                ],
            ]
        );
        $this->end_controls_section();

        $this->start_controls_section(
            'style_section_spacing',
            [
                'label' => __( 'فاصله‌گذاری', 'yakutlogin' ),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );
        $this->add_responsive_control(
            'field_margin_bottom',
            [
                'label' => __( 'فاصله بین فیلدها', 'yakutlogin' ),
                'type' => Controls_Manager::SLIDER,
                'size_units' => [ 'px' ],
                'range' => [ 'px' => [ 'min' => 0, 'max' => 50 ] ],
                'default' => [ 'unit' => 'px', 'size' => 15 ],
                'selectors' => [
                    '{{WRAPPER}} .slr-otp-form-container .slr-form-row' => 'margin-bottom: {{SIZE}}{{UNIT}};',
                ],
            ]
        );
        $this->add_responsive_control(
            'label_margin_bottom',
            [
                'label' => __( 'فاصله زیر برچسب‌ها', 'yakutlogin' ),
                'type' => Controls_Manager::SLIDER,
                'size_units' => [ 'px' ],
                'range' => [ 'px' => [ 'min' => 0, 'max' => 30 ] ],
                'default' => [ 'unit' => 'px', 'size' => 5 ],
                'selectors' => [ '{{WRAPPER}} .slr-otp-form-container label' => 'margin-bottom: {{SIZE}}{{UNIT}};' ],
                'condition' => [ 'show_labels' => 'yes' ],
            ]
        );
        $this->end_controls_section();
    }

    protected function render() {
    $settings = $this->get_settings_for_display();
    
    $public_class_instance = null;
    
    // اطمینان از وجود کلاس‌های مورد نیاز
    if (class_exists('Sms_Login_Register_Public') && class_exists('SLR_Theme_Manager')) {
        $plugin_name = defined('SLR_PLUGIN_NAME_FOR_INSTANCE') ? SLR_PLUGIN_NAME_FOR_INSTANCE : 'sms-login-register';
        $plugin_version = defined('SLR_PLUGIN_VERSION_FOR_INSTANCE') ? SLR_PLUGIN_VERSION_FOR_INSTANCE : '1.9.1';
        

        $theme_manager = \SLR_Theme_Manager::get_instance();
        
        
        $public_class_instance = new \Sms_Login_Register_Public($plugin_name, $plugin_version, $theme_manager);
        
    }

    if ($public_class_instance) {
    $button_texts = [
        // استفاده از Null Coalescing Operator برای ارائه مقدار پیش‌فرض
        'send_otp' => $settings['text_send_otp'] ?? __( 'ارسال کد تایید', 'yakutlogin' ),
        'submit'   => $settings['text_submit'] ?? __( 'ورود / عضویت', 'yakutlogin' ),
        'google'   => $settings['text_google'] ?? __( 'ورود با گوگل', 'yakutlogin' ),
    ];

    $form_args = [
        // استفاده از Null Coalescing Operator برای تمام مقادیر
        'context'     => $settings['form_context'] ?? 'mixed',
        'show_labels' => ($settings['show_labels'] ?? 'yes') === 'yes',
        'redirect_to' => $settings['redirect_to']['url'] ?? '',
        'theme'       => $settings['form_theme'] ?? 'default',
        'layout'      => $settings['form_layout'] ?? 'default',
        'button_texts'=> $button_texts,
    ];
    
    echo $public_class_instance->get_otp_form_html( $form_args );

} else {
        echo __('خطا: کلاس اصلی افزونه یافت نشد.', 'yakutlogin');
    }
}

    protected function content_template() {
        ?>
        <div class="elementor-widget-slr-otp-form-placeholder">
             <p><strong><?php _e('پیش‌نمایش فرم ورود پیامکی', 'yakutlogin'); ?></strong></p>
             <p><em><?php _e('فرم اصلی در صفحه سایت نمایش داده خواهد شد.', 'yakutlogin'); ?></em></p>
        </div>
        <?php
    }
}