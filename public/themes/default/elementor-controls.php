<?php
/**
 * این فایل شامل کنترل‌های اختصاصی المنتور برای پوسته پیش‌فرض است.
 * این کنترل‌ها فقط زمانی نمایش داده می‌شوند که پوسته "پیش‌فرض" انتخاب شده باشد.
 */
if ( ! defined( 'ABSPATH' ) ) exit;

use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Box_Shadow;

/**
 * @var \Elementor\Widget_Base $widget The widget instance being edited.
 */

//======================================================================
// بخش استایل فیلدهای ورودی
//======================================================================
$widget->start_controls_section(
    'section_style_fields_default',
    [
        'label' => __( 'استایل فیلدهای ورودی', 'yakutlogin' ),
        'tab' => Controls_Manager::TAB_STYLE,
        'condition' => [
            'form_theme' => 'default', // شرط نمایش: فقط برای پوسته default
        ],
    ]
);

$widget->add_group_control(
    Group_Control_Typography::get_type(),
    [
        'name' => 'field_typography',
        'label' => __( 'تایپوگرافی فیلدها', 'yakutlogin' ),
        'selector' => '{{WRAPPER}} .slr-theme-default .slr-input',
    ]
);

$widget->add_control(
    'field_text_color',
    [
        'label' => __( 'رنگ متن', 'yakutlogin' ),
        'type' => Controls_Manager::COLOR,
        'selectors' => [
            '{{WRAPPER}} .slr-theme-default .slr-input' => 'color: {{VALUE}};',
            '{{WRAPPER}} .slr-theme-default .slr-input::placeholder' => 'color: {{VALUE}}; opacity: 0.7;',
        ],
    ]
);

$widget->add_control(
    'field_bg_color',
    [
        'label' => __( 'رنگ پس‌زمینه', 'yakutlogin' ),
        'type' => Controls_Manager::COLOR,
        'selectors' => [
            '{{WRAPPER}} .slr-theme-default .slr-input' => 'background-color: {{VALUE}};',
        ],
    ]
);

$widget->add_group_control(
    Group_Control_Border::get_type(),
    [
        'name' => 'field_border',
        'label' => __( 'بردر (حاشیه)', 'yakutlogin' ),
        'selector' => '{{WRAPPER}} .slr-theme-default .slr-input',
    ]
);

$widget->add_responsive_control(
    'field_border_radius',
    [
        'label' => __( 'گردی گوشه‌ها', 'yakutlogin' ),
        'type' => Controls_Manager::DIMENSIONS,
        'size_units' => [ 'px', '%' ],
        'selectors' => [
            '{{WRAPPER}} .slr-theme-default .slr-input' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
        ],
    ]
);

$widget->end_controls_section();


//======================================================================
// بخش استایل دکمه‌ها
//======================================================================
$widget->start_controls_section(
    'section_style_buttons_default',
    [
        'label' => __( 'استایل دکمه‌ها', 'yakutlogin' ),
        'tab' => Controls_Manager::TAB_STYLE,
        'condition' => [
            'form_theme' => 'default', // شرط نمایش
        ],
    ]
);

$widget->add_group_control(
    Group_Control_Typography::get_type(),
    [
        'name' => 'button_typography',
        'label' => __( 'تایپوگرافی دکمه', 'yakutlogin' ),
        'selector' => '{{WRAPPER}} .slr-theme-default .slr-button',
    ]
);

$widget->add_responsive_control(
    'button_width',
    [
        'label' => __( 'عرض دکمه', 'yakutlogin' ),
        'type' => Controls_Manager::SLIDER,
        'size_units' => [ 'px', '%', 'vw' ],
        'range' => [
            '%' => [ 'min' => 10, 'max' => 100 ],
            'px' => [ 'min' => 50, 'max' => 500 ],
        ],
        'default' => [
            'unit' => '%',
            'size' => 100,
        ],
        'selectors' => [
            '{{WRAPPER}} .slr-theme-default .slr-button' => 'width: {{SIZE}}{{UNIT}};',
        ],
    ]
);

// --- تب‌های حالت عادی و هاور برای دکمه ---
$widget->start_controls_tabs( 'button_style_tabs' );

// -- حالت عادی --
$widget->start_controls_tab(
    'button_normal_tab',
    [
        'label' => __( 'عادی', 'yakutlogin' ),
    ]
);

$widget->add_control(
    'button_text_color_normal',
    [
        'label' => __( 'رنگ متن', 'yakutlogin' ),
        'type' => Controls_Manager::COLOR,
        'selectors' => [
            '{{WRAPPER}} .slr-theme-default .slr-button' => 'color: {{VALUE}};',
        ],
    ]
);

$widget->add_control(
    'button_bg_color_normal',
    [
        'label' => __( 'رنگ پس‌زمینه', 'yakutlogin' ),
        'type' => Controls_Manager::COLOR,
        'selectors' => [
            '{{WRAPPER}} .slr-theme-default .slr-button' => 'background-color: {{VALUE}};',
        ],
    ]
);

$widget->add_group_control(
    Group_Control_Border::get_type(),
    [
        'name' => 'button_border_normal',
        'selector' => '{{WRAPPER}} .slr-theme-default .slr-button',
    ]
);

$widget->add_group_control(
    Group_Control_Box_Shadow::get_type(),
    [
        'name' => 'button_box_shadow_normal',
        'selector' => '{{WRAPPER}} .slr-theme-default .slr-button',
    ]
);

$widget->end_controls_tab();


// -- حالت هاور (Hover) --
$widget->start_controls_tab(
    'button_hover_tab',
    [
        'label' => __( 'هاور', 'yakutlogin' ),
    ]
);

$widget->add_control(
    'button_text_color_hover',
    [
        'label' => __( 'رنگ متن هاور', 'yakutlogin' ),
        'type' => Controls_Manager::COLOR,
        'selectors' => [
            '{{WRAPPER}} .slr-theme-default .slr-button:hover' => 'color: {{VALUE}};',
        ],
    ]
);

$widget->add_control(
    'button_bg_color_hover',
    [
        'label' => __( 'رنگ پس‌زمینه هاور', 'yakutlogin' ),
        'type' => Controls_Manager::COLOR,
        'selectors' => [
            '{{WRAPPER}} .slr-theme-default .slr-button:hover' => 'background-color: {{VALUE}};',
        ],
    ]
);

$widget->add_control(
    'button_border_color_hover',
    [
        'label' => __( 'رنگ بردر هاور', 'yakutlogin' ),
        'type' => Controls_Manager::COLOR,
        'selectors' => [
            '{{WRAPPER}} .slr-theme-default .slr-button:hover' => 'border-color: {{VALUE}};',
        ],
    ]
);

$widget->add_group_control(
    Group_Control_Box_Shadow::get_type(),
    [
        'name' => 'button_box_shadow_hover',
        'selector' => '{{WRAPPER}} .slr-theme-default .slr-button:hover',
    ]
);

$widget->add_control(
    'hover_animation',
    [
        'label' => __( 'انیمیشن هاور', 'yakutlogin' ),
        'type' => \Elementor\Controls_Manager::HOVER_ANIMATION,
    ]
);

$widget->end_controls_tab();
$widget->end_controls_tabs();

$widget->add_control(
    'hr_button_styles',
    [
        'type' => Controls_Manager::DIVIDER,
        'separator' => 'before',
    ]
);

$widget->add_responsive_control(
    'button_border_radius',
    [
        'label' => __( 'گردی گوشه‌ها', 'yakutlogin' ),
        'type' => Controls_Manager::DIMENSIONS,
        'size_units' => [ 'px', '%' ],
        'selectors' => [
            '{{WRAPPER}} .slr-theme-default .slr-button' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
        ],
    ]
);

$widget->add_control(
    'transition_duration',
    [
        'label' => __( 'سرعت انیمیشن (ثانیه)', 'yakutlogin' ),
        'type' => Controls_Manager::SLIDER,
        'default' => [ 'size' => 0.3 ],
        'range' => [ 'px' => [ 'max' => 3, 'step' => 0.1 ] ],
        'selectors' => [
            '{{WRAPPER}} .slr-theme-default .slr-button' => 'transition: all {{SIZE}}s ease;',
        ],
    ]
);

$widget->end_controls_section();