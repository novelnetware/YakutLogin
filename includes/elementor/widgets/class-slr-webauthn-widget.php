<?php
namespace Sms_Login_Register_Elementor\Widgets;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Box_Shadow;

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

class SLR_WebAuthn_Widget extends Widget_Base {

    public function get_name() {
        return 'slr-webauthn-register-button';
    }

    public function get_title() {
        return __( 'دکمه ثبت دستگاه (WebAuthn)', 'yakutlogin' );
    }

    public function get_icon() {
        // استفاده از آیکون اثر انگشت از کتابخانه استاندارد المنتور
         return 'eicon-button';
    }

    public function get_categories() {
        return [ 'slr-elements' ]; // قرارگیری در دسته‌بندی اختصاصی شما
    }

    public function get_keywords() {
        return [ 'webauthn', 'biometric', 'passwordless', 'button', 'yakut', 'یاقوت', 'اثر انگشت' ];
    }

    protected function register_controls() {

        // --- تب محتوا: تنظیمات اصلی دکمه ---
        $this->start_controls_section(
            'section_content_button',
            [
                'label' => __( 'محتوای دکمه', 'yakutlogin' ),
            ]
        );

        $this->add_control(
            'button_text',
            [
                'label' => __( 'متن دکمه', 'yakutlogin' ),
                'type' => Controls_Manager::TEXT,
                'default' => __( 'ثبت دستگاه برای ورود بدون رمز', 'yakutlogin' ),
                'placeholder' => __( 'متن دکمه را وارد کنید', 'yakutlogin' ),
            ]
        );

        $this->add_control(
            'selected_icon',
            [
                'label' => __( 'آیکون SVG', 'yakutlogin' ),
                'type' => Controls_Manager::MEDIA,
                'media_types' => [ 'svg' ],
                'description' => __( 'یک فایل SVG برای آیکون دکمه آپلود کنید.', 'yakutlogin' ),
            ]
        );
        
        $this->add_responsive_control(
            'align',
            [
                'label' => __( 'چینش', 'yakutlogin' ),
                'type' => Controls_Manager::CHOOSE,
                'options' => [
                    'left'    => [
                        'title' => __( 'چپ', 'yakutlogin' ),
                        'icon' => 'eicon-text-align-left',
                    ],
                    'center' => [
                        'title' => __( 'وسط', 'yakutlogin' ),
                        'icon' => 'eicon-text-align-center',
                    ],
                    'right' => [
                        'title' => __( 'راست', 'yakutlogin' ),
                        'icon' => 'eicon-text-align-right',
                    ],
                    'justify' => [
                        'title' => __( 'کشیده', 'yakutlogin' ),
                        'icon' => 'eicon-text-align-justify',
                    ],
                ],
                'default' => 'center',
                'selectors' => [
                    '{{WRAPPER}} .slr-webauthn-widget-wrapper' => 'text-align: {{VALUE}};',
                ],
            ]
        );

        $this->end_controls_section();


        // --- تب استایل: تنظیمات کامل ظاهر ---
        $this->start_controls_section(
            'section_style_button',
            [
                'label' => __( 'استایل دکمه', 'yakutlogin' ),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'typography',
                'selector' => '{{WRAPPER}} .slr-webauthn-button',
            ]
        );
        
        $this->add_responsive_control(
            'padding',
            [
                'label' => __( 'پدینگ', 'yakutlogin' ),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => [ 'px', 'em', '%' ],
                'selectors' => [
                    '{{WRAPPER}} .slr-webauthn-button' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );
        
        // --- دو حالت عادی و هاور ---
        $this->start_controls_tabs( 'tabs_button_style' );

        $this->start_controls_tab(
            'tab_button_normal',
            [
                'label' => __( 'عادی', 'yakutlogin' ),
            ]
        );

        $this->add_control(
            'button_text_color',
            [
                'label' => __( 'رنگ متن', 'yakutlogin' ),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .slr-webauthn-button' => 'color: {{VALUE}};',
                    '{{WRAPPER}} .slr-webauthn-button .slr-btn-icon svg' => 'fill: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'background_color',
            [
                'label' => __( 'رنگ پس‌زمینه', 'yakutlogin' ),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .slr-webauthn-button' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Border::get_type(),
            [
                'name' => 'border',
                'selector' => '{{WRAPPER}} .slr-webauthn-button',
            ]
        );
        
        $this->add_group_control(
            Group_Control_Box_Shadow::get_type(),
            [
                'name' => 'button_box_shadow',
                'selector' => '{{WRAPPER}} .slr-webauthn-button',
            ]
        );

        $this->end_controls_tab();

        $this->start_controls_tab(
            'tab_button_hover',
            [
                'label' => __( 'هاور', 'yakutlogin' ),
            ]
        );

        $this->add_control(
            'hover_color',
            [
                'label' => __( 'رنگ متن', 'yakutlogin' ),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .slr-webauthn-button:hover' => 'color: {{VALUE}};',
                    '{{WRAPPER}} .slr-webauthn-button:hover .slr-btn-icon svg' => 'fill: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'button_background_hover_color',
            [
                'label' => __( 'رنگ پس‌زمینه', 'yakutlogin' ),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .slr-webauthn-button:hover' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'border_hover_color',
            [
                'label' => __( 'رنگ بردر', 'yakutlogin' ),
                'type' => Controls_Manager::COLOR,
                'condition' => [
                    'border_border!' => '',
                ],
                'selectors' => [
                    '{{WRAPPER}} .slr-webauthn-button:hover' => 'border-color: {{VALUE}};',
                ],
            ]
        );
        
        $this->add_group_control(
            Group_Control_Box_Shadow::get_type(),
            [
                'name' => 'button_hover_box_shadow',
                'selector' => '{{WRAPPER}} .slr-webauthn-button:hover',
            ]
        );

        $this->end_controls_tab();

        $this->end_controls_tabs();

        $this->end_controls_section();
        
        // --- بخش استایل آیکون ---
        $this->start_controls_section(
            'section_icon_style',
            [
                'label' => __( 'استایل آیکون', 'yakutlogin' ),
                'tab' => Controls_Manager::TAB_STYLE,
                'condition' => [
                    'selected_icon[url]!' => '',
                ],
            ]
        );

        $this->add_responsive_control(
            'icon_size',
            [
                'label' => __( 'اندازه آیکون', 'yakutlogin' ),
                'type' => Controls_Manager::SLIDER,
                'range' => [
                    'px' => [
                        'min' => 6,
                        'max' => 100,
                    ],
                ],
                'selectors' => [
                    '{{WRAPPER}} .slr-btn-icon' => 'font-size: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'icon_position',
            [
                'label' => __( 'موقعیت آیکون', 'yakutlogin' ),
                'type' => Controls_Manager::SELECT,
                'default' => 'before',
                'options' => [
                    'before' => __( 'قبل از متن', 'yakutlogin' ),
                    'after' => __( 'بعد از متن', 'yakutlogin' ),
                ],
                'prefix_class' => 'slr-icon-position-',
            ]
        );

        $this->add_responsive_control(
            'icon_spacing',
            [
                'label' => __( 'فاصله از متن', 'yakutlogin' ),
                'type' => Controls_Manager::SLIDER,
                'range' => [
                    'px' => [
                        'max' => 50,
                    ],
                ],
                'selectors' => [
                    '{{WRAPPER}}.slr-icon-position-after .slr-btn-icon' => 'margin-right: {{SIZE}}{{UNIT}};',
                    '{{WRAPPER}}.slr-icon-position-before .slr-btn-icon' => 'margin-left: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();
    }

    protected function render() {
        if ( !is_user_logged_in() || empty(get_option('slr_plugin_options')['webauthn_enabled']) ) {
            return; // ویجت را فقط برای کاربران وارد شده و در صورت فعال بودن نمایش بده
        }

        $settings = $this->get_settings_for_display();

        $this->add_render_attribute( 'wrapper', 'class', 'slr-webauthn-widget-wrapper' );
        
        $this->add_render_attribute( 'button', [
            'class' => [ 'slr-webauthn-button', 'slr-frontend-register-btn' ], // کلاس عمومی برای جاوااسکریپت
            'role' => 'button',
        ]);
        
        ?>
        <div <?php echo $this->get_render_attribute_string( 'wrapper' ); ?>>
            <a <?php echo $this->get_render_attribute_string( 'button' ); ?>>
                <?php if ( ! empty( $settings['selected_icon']['url'] ) && $settings['icon_position'] === 'before' ) : ?>
                    <span class="slr-btn-icon">
                        <img src="<?php echo esc_url( $settings['selected_icon']['url'] ); ?>" alt="Icon">
                    </span>
                <?php endif; ?>

                <span class="slr-btn-text"><?php echo esc_html( $settings['button_text'] ); ?></span>

                <?php if ( ! empty( $settings['selected_icon']['url'] ) && $settings['icon_position'] === 'after' ) : ?>
                    <span class="slr-btn-icon">
                        <img src="<?php echo esc_url( $settings['selected_icon']['url'] ); ?>" alt="Icon">
                    </span>
                <?php endif; ?>
            </a>
            <div class="slr-webauthn-message" style="margin-top:10px;"></div>
        </div>
        <?php
    }

    protected function _content_template() {
        ?>
        <#
        view.addRenderAttribute( 'button', 'class', 'slr-webauthn-button' );
        #>
        <div class="slr-webauthn-widget-wrapper">
             <a {{{ view.getRenderAttributeString( 'button' ) }}}>
                <# if ( settings.selected_icon.url && settings.icon_position === 'before' ) { #>
                    <span class="slr-btn-icon">
                        <img src="{{{ settings.selected_icon.url }}}" alt="Icon">
                    </span>
                <# } #>
                <span class="slr-btn-text">{{{ settings.button_text }}}</span>
                <# if ( settings.selected_icon.url && settings.icon_position === 'after' ) { #>
                    <span class="slr-btn-icon">
                        <img src="{{{ settings.selected_icon.url }}}" alt="Icon">
                    </span>
                <# } #>
            </a>
        </div>
        <?php
    }
}