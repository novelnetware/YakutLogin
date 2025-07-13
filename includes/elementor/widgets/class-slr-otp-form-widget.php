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

        $this->add_control(
    'form_logo',
    [
        'label' => __( 'لوگوی فرم', 'yakutlogin' ),
        'type' => Controls_Manager::MEDIA,
        'default' => [
            'url' => '',
        ],
        'description' => 'یک لوگو برای نمایش در بالای فرم انتخاب کنید.',
    ]
);

        // بررسی وجود کلاس Theme Manager
        $available_themes = $this->get_available_themes();

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

        

        $this->end_controls_section();

        // ===================================================
// START: بخش جدید برای سفارشی‌سازی دکمه‌های اجتماعی
// ===================================================
$this->start_controls_section(
    'content_section_social_buttons',
    [
        'label' => __( 'سفارشی‌سازی دکمه‌های اجتماعی', 'yakutlogin' ),
        'tab' => Controls_Manager::TAB_CONTENT,
    ]
);

$social_providers = [
    'google' => 'گوگل',
    'bale' => 'بله',
    'discord' => 'دیسکورد',
    'linkedin' => 'لینکدین',
    'github' => 'گیت‌هاب',
];

foreach ($social_providers as $id => $label) {
    $this->add_control(
        'text_' . $id,
        [
            'label' => sprintf(__( 'متن دکمه %s', 'yakutlogin' ), $label),
            'type' => Controls_Manager::TEXT,
            'default' => sprintf(__( 'ورود با %s', 'yakutlogin' ), $label),
            'condition' => [ 'social_buttons_layout' => 'default' ],
        ]
    );
    $this->add_control(
        'icon_' . $id,
        [
            'label' => sprintf(__( 'آیکون %s', 'yakutlogin' ), $label),
            'type' => Controls_Manager::ICONS,
            'skin' => 'inline',
            'label_block' => false,
        ]
    );
    $this->add_control(
        'hr_' . $id,
        [ 'type' => Controls_Manager::DIVIDER ]
    );
}

$this->end_controls_section();
// =================================================
// END: بخش جدید برای سفارشی‌سازی دکمه‌های اجتماعی
// =================================================

        // --- بخش کنترل‌های داینامیک پوسته‌ها ---
        $this->register_theme_controls();

        // --- Style Tab ---
        $this->register_style_controls();
    }

    /**
     * دریافت لیست پوسته‌های موجود
     */
    private function get_available_themes() {
        // بررسی وجود کلاس Theme Manager
        if ( class_exists( 'SLR_Theme_Manager' ) ) {
            $theme_manager = \SLR_Theme_Manager::get_instance();
            if ( $theme_manager && method_exists( $theme_manager, 'get_themes_for_select' ) ) {
                return $theme_manager->get_themes_for_select();
            }
        }
        
        // اگر Theme Manager موجود نیست، پوسته پیش‌فرض را برگردان
        return [
            'default' => __('پیش‌فرض', 'yakutlogin'),
            'modern' => __('مدرن', 'yakutlogin'),
            'minimal' => __('مینیمال', 'yakutlogin'),
        ];
    }

    /**
     * ثبت کنترل‌های پوسته‌ها
     */
    private function register_theme_controls() {
        // بررسی وجود کلاس Theme Manager
        if ( ! class_exists( 'SLR_Theme_Manager' ) ) {
            return;
        }

        $theme_manager = \SLR_Theme_Manager::get_instance();
        if ( ! $theme_manager ) {
            return;
        }

        $available_themes = $this->get_available_themes();

        foreach ( $available_themes as $id => $name ) {
            $theme_object = $theme_manager->get_theme( $id );
            if ( $theme_object && method_exists( $theme_object, 'register_elementor_controls' ) ) {
                $theme_object->register_elementor_controls( $this );
            }
        }
    }

    /**
     * ثبت کنترل‌های استایل
     */
    private function register_style_controls() {
        // کادر فرم
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
        
        // برچسب‌ها
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

        // فیلدهای ورودی
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

        // دکمه‌ها
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

        // فاصله‌گذاری
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

        // ورود با شبکه‌های اجتماعی
        $this->register_social_login_controls();
    }

    /**
     * ثبت کنترل‌های ورود اجتماعی
     */
private function register_social_login_controls() {
    $this->start_controls_section(
        'section_style_social_icons',
        [
            'label' => __( 'آیکون‌های ورود اجتماعی', 'yakutlogin' ),
            'tab' => Controls_Manager::TAB_STYLE,
        ]
    );

    $this->add_responsive_control(
        'social_icons_gap',
        [
            'label' => __( 'فاصله بین آیکون‌ها', 'yakutlogin' ),
            'type' => Controls_Manager::SLIDER,
            'size_units' => [ 'px' ],
            'default' => [ 'unit' => 'px', 'size' => 10 ],
            'selectors' => [
                '{{WRAPPER}} .slr-social-icons-wrapper .icon' => 'margin: 0 {{SIZE}}{{UNIT}};',
            ],
        ]
    );

    // تب‌های استایل برای هر سرویس
    $social_providers = ['google', 'bale', 'discord', 'linkedin', 'github'];
    foreach ($social_providers as $provider) {
        $this->add_control(
            $provider . '_style_heading',
            [
                'label' => __( 'استایل ' . ucfirst($provider), 'yakutlogin' ),
                'type' => Controls_Manager::HEADING,
                'separator' => 'before',
            ]
        );

        $this->start_controls_tabs( 'social_icon_tabs_' . $provider );

        // حالت عادی
        $this->start_controls_tab(
            'social_icon_normal_tab_' . $provider,
            [ 'label' => __( 'عادی', 'yakutlogin' ) ]
        );
        $this->add_control(
            $provider . '_icon_color',
            [
                'label' => __( 'رنگ آیکون', 'yakutlogin' ),
                'type' => Controls_Manager::COLOR,
                'selectors' => [ '{{WRAPPER}} .slr-social-icons-wrapper .' . $provider . ' span i' => 'color: {{VALUE}};' ],
            ]
        );
        $this->add_control(
            $provider . '_bg_color',
            [
                'label' => __( 'رنگ پس‌زمینه', 'yakutlogin' ),
                'type' => Controls_Manager::COLOR,
                'default' => '#ffffff',
                'selectors' => [ '{{WRAPPER}} .slr-social-icons-wrapper .' . $provider => 'background: {{VALUE}};' ],
            ]
        );
        $this->end_controls_tab();

        // حالت هاور
        $this->start_controls_tab(
            'social_icon_hover_tab_' . $provider,
            [ 'label' => __( 'هاور', 'yakutlogin' ) ]
        );
        $this->add_control(
            $provider . '_icon_color_hover',
            [
                'label' => __( 'رنگ آیکون', 'yakutlogin' ),
                'type' => Controls_Manager::COLOR,
                'default' => '#ffffff',
                'selectors' => [ '{{WRAPPER}} .slr-social-icons-wrapper .' . $provider . ':hover span i' => 'color: {{VALUE}};' ],
            ]
        );
        $this->add_control(
            $provider . '_bg_color_hover',
            [
                'label' => __( 'رنگ پس‌زمینه', 'yakutlogin' ),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .slr-social-icons-wrapper .' . $provider . ':hover, {{WRAPPER}} .slr-social-icons-wrapper .' . $provider . ':hover .tooltip, {{WRAPPER}} .slr-social-icons-wrapper .' . $provider . ':hover .tooltip::before' => 'background: {{VALUE}};',
                ],
            ]
        );
        $this->add_control(
            $provider . '_tooltip_text_color',
            [
                'label' => __( 'رنگ متن تولتیپ', 'yakutlogin' ),
                'type' => Controls_Manager::COLOR,
                'default' => '#ffffff',
                'selectors' => [ '{{WRAPPER}} .slr-social-icons-wrapper .' . $provider . ':hover .tooltip' => 'color: {{VALUE}};' ],
            ]
        );
        $this->end_controls_tab();
        $this->end_controls_tabs();
    }
    $this->end_controls_section();
}

protected function render() {
    $settings = $this->get_settings_for_display();

    if ( ! function_exists( 'do_shortcode' ) ) {
        return;
    }

    // ساخت attributes برای shortcode
    $shortcode_atts = [
        'theme'       => $settings['form_theme'] ?? 'default',
        'layout'      => $settings['form_layout'] ?? 'default',
        'show_labels' => ( $settings['show_labels'] ?? 'yes' ) === 'yes' ? 'true' : 'false',
        'redirect_to' => $settings['redirect_to']['url'] ?? '',
    ];

    if ( ! empty( $settings['form_logo']['url'] ) ) {
    $shortcode_atts['logo_url'] = $settings['form_logo']['url'];
}

    // افزودن متن و آیکون‌های سفارشی به شورت‌کد
    $social_providers = ['google', 'telegram', 'bale', 'discord', 'linkedin', 'github'];
    foreach ($social_providers as $provider) {
        if (!empty($settings['text_' . $provider])) {
            $shortcode_atts['text_' . $provider] = $settings['text_' . $provider];
        }
        if (!empty($settings['icon_' . $provider]['value'])) {
            // برای ارسال آیکون به صورت امن، نام کلاس آن را می‌فرستیم
            $shortcode_atts['icon_' . $provider] = is_array($settings['icon_' . $provider]['value']) ? implode(' ', $settings['icon_' . $provider]['value']) : $settings['icon_' . $provider]['value'];
        }
    }
    
    // افزودن متن دکمه‌های اصلی
    $shortcode_atts['text_send_otp'] = $settings['text_send_otp'];
    $shortcode_atts['text_submit'] = $settings['text_submit'];

    $atts_string = '';
    foreach ( $shortcode_atts as $key => $value ) {
        if ( ! empty( $value ) || $value === '0' ) {
            $atts_string .= sprintf( ' %s="%s"', $key, esc_attr( $value ) );
        }
    }

    echo do_shortcode( '[slr_otp_form' . $atts_string . ']' );
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