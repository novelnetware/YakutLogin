<?php
if ( ! defined( 'ABSPATH' ) ) exit;

// این اینترفیس یک قرارداد است که تمام کلاس‌های پوسته باید از آن پیروی کنند.
interface SLR_Theme {
    public function __construct(array $theme_data);
    public function get_theme_data(): array;
    public function get_html(array $args): string;
    public function register_elementor_controls(\Elementor\Widget_Base $widget);
    public function get_assets(): array;
}