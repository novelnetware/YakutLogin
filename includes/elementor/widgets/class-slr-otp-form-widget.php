<?php
namespace Sms_Login_Register_Elementor\Widgets;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

/**
 * Elementor OTP Form Widget.
 *
 * Elementor widget for displaying an OTP login/registration form.
 *
 * @since 1.0.1
 */
class SLR_Otp_Form_Widget extends Widget_Base {

    public function get_name() {
        return 'slr-otp-form';
    }

    public function get_title() {
        return __( 'SMS/OTP Login Form', 'sms-login-register' );
    }

    public function get_icon() {
        return 'eicon-lock-user'; // Choose an appropriate icon
    }

    public function get_categories() {
        // return [ 'slr-elements' ]; // For custom category
        return [ 'general' ]; // Or 'basic' or a common category
    }

    public function get_keywords() {
        return [ 'otp', 'sms', 'login', 'register', 'form', 'sms login register' ];
    }

    protected function register_controls() {
        $this->start_controls_section(
            'content_section',
            [
                'label' => __( 'Form Settings', 'sms-login-register' ),
                'tab' => Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'form_context',
            [
                'label' => __( 'Form Type', 'sms-login-register' ),
                'type' => Controls_Manager::SELECT,
                'default' => 'mixed',
                'options' => [
                    'mixed'  => __( 'Login & Register (Auto-detect)', 'sms-login-register' ),
                    'login' => __( 'Login Only', 'sms-login-register' ),
                    'register' => __( 'Register Only', 'sms-login-register' ),
                ],
                'description' => __('Determines if the form is for login, registration, or both.', 'sms-login-register'),
            ]
        );

        $this->add_control(
            'show_labels',
            [
                'label' => __( 'Show Labels', 'sms-login-register' ),
                'type' => Controls_Manager::SWITCHER,
                'label_on' => __( 'Show', 'sms-login-register' ),
                'label_off' => __( 'Hide', 'sms-login-register' ),
                'return_value' => 'yes',
                'default' => 'yes',
            ]
        );
        
        $this->add_control(
            'redirect_to',
            [
                'label' => __( 'Redirect After Success', 'sms-login-register' ),
                'type' => Controls_Manager::URL,
                'placeholder' => __( 'https://your-site.com/my-account/', 'sms-login-register' ),
                'show_external' => true,
                'default' => [
                    'url' => '',
                    'is_external' => false,
                    'nofollow' => false,
                ],
                'description' => __('Leave empty for default redirect (usually dashboard or previous page).', 'sms-login-register'),
            ]
        );

        $this->add_control(
            'form_context',
            [
                'label' => __( 'Form Type', 'sms-login-register' ),
                'type' => Controls_Manager::SELECT,
                'default' => 'mixed',
                'options' => [
                    'mixed'  => __( 'Login & Register (Auto-detect)', 'sms-login-register' ),
                    // 'login' => __( 'Login Only', 'sms-login-register' ), // Can enable if needed
                    // 'register' => __( 'Register Only', 'sms-login-register' ), // Can enable if needed
                ],
            ]
        );
        $this->add_control(
            'show_labels',
            [
                'label' => __( 'Show Field Labels', 'sms-login-register' ),
                'type' => Controls_Manager::SWITCHER,
                'label_on' => __( 'Show', 'sms-login-register' ),
                'label_off' => __( 'Hide', 'sms-login-register' ),
                'return_value' => 'yes',
                'default' => 'yes',
            ]
        );
        $this->add_control(
            'form_layout', // New control for layout selection
            [
                'label' => __( 'Form Layout', 'sms-login-register' ),
                'type' => Controls_Manager::SELECT,
                'default' => 'default',
                'options' => [
                    'default'  => __( 'Default (Labels on Top)', 'sms-login-register' ),
                    'compact' => __( 'Compact (Smaller spacing, labels on top)', 'sms-login-register' ),
                    'inline_labels' => __( 'Inline Labels (Labels as placeholders)', 'sms-login-register' ),
                    // Add more layouts as you define them
                ],
            ]
        );
         $this->add_control(
            'form_theme', // New control for theme selection
            [
                'label' => __( 'Form Theme', 'sms-login-register' ),
                'type' => Controls_Manager::SELECT,
                'default' => 'default',
                'options' => [
                    'default'  => __( 'Default', 'sms-login-register' ),
                    'minimal' => __( 'Minimal', 'sms-login-register' ),
                    'dark'    => __( 'Dark (Example)', 'sms-login-register' ),
                    // Add more themes as you create them
                ],
            ]
        );
        $this->add_control(
            'redirect_to',
            [
                'label' => __( 'Redirect URL After Success', 'sms-login-register' ),
                'type' => Controls_Manager::URL,
                'placeholder' => __( 'https://your-site.com/my-account/', 'sms-login-register' ),
                'show_external' => true,
                'default' => [ 'url' => '' ],
            ]
        );

        $this->add_control(
            'hr_buttons',
            [
                'type' => Controls_Manager::DIVIDER,
            ]
        );

        this->add_control(
            'text_send_otp',
            [
                'label' => __( 'Send OTP Button Text', 'sms-login-register' ),
                'type' => Controls_Manager::TEXT,
                'default' => __( 'Send OTP', 'sms-login-register' ),
                'placeholder' => __( 'Send OTP', 'sms-login-register' ),
            ]
        );
        $this->add_control(
            'text_submit',
            [
                'label' => __( 'Submit Button Text', 'sms-login-register' ),
                'type' => Controls_Manager::TEXT,
                'default' => __( 'Login / Register with OTP', 'sms-login-register' ),
                'placeholder' => __( 'Login / Register with OTP', 'sms-login-register' ),
            ]
        );
        $this->add_control(
            'text_google',
            [
                'label' => __( 'Google Button Text', 'sms-login-register' ),
                'type' => Controls_Manager::TEXT,
                'default' => __( 'Login with Google', 'sms-login-register' ),
                'placeholder' => __( 'Login with Google', 'sms-login-register' ),
                 'condition' => [ // Only show if Google login might be enabled globally
                    // You might need a global setting check here if possible,
                    // or just always show it and let it depend on plugin settings.
                ],
            ]
        );
       

        // Add more controls for styling later (e.g., button text, colors, spacing)

        $this->end_controls_section();

         // --- Style Tab ---
         $this->start_controls_section(
            'style_section_container',
            [
                'label' => __( 'Form Container', 'sms-login-register' ),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );
        $this->add_responsive_control(
            'container_padding',
            [
                'label' => __( 'Padding', 'sms-login-register' ),
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
                'label' => __( 'Background Color', 'sms-login-register' ),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .slr-otp-form-container' => 'background-color: {{VALUE}};',
                ],
            ]
        );
        $this->end_controls_section();
        
        // Input Fields Styling
        $this->start_controls_section(
            'style_section_fields',
            [
                'label' => __( 'Input Fields', 'sms-login-register' ),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );
        // Add controls for input field typography, color, background, border, padding etc.
        // Example for input padding:
        $this->add_responsive_control(
            'input_padding',
            [
                'label' => __( 'Padding', 'sms-login-register' ),
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
                'label' => __( 'Text Color', 'sms-login-register' ),
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
                'label' => __( 'Background Color', 'sms-login-register' ),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .slr-otp-form-container .slr-input' => 'background-color: {{VALUE}};',
                ],
            ]
        );
        // Add more (border, typography group control, etc.)
        $this->end_controls_section();

        // Button Styling (can create separate sections for Send OTP and Submit buttons)
        $this->start_controls_section(
            'style_section_buttons',
            [
                'label' => __( 'Buttons', 'sms-login-register' ),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );
        // Add controls for button typography, color, background, border, padding etc.
        // Example for button background color:
         $this->add_control(
            'button_bg_color',
            [
                'label' => __( 'Background Color', 'sms-login-register' ),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .slr-otp-form-container .slr-button' => 'background-color: {{VALUE}}; border-color: {{VALUE}};',
                ],
            ]
        );
         $this->add_control(
            'button_text_color',
            [
                'label' => __( 'Text Color', 'sms-login-register' ),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .slr-otp-form-container .slr-button' => 'color: {{VALUE}};',
                ],
            ]
        );
        // Add more (padding, border radius, hover states)
        $this->end_controls_section();

        // Example: Add more granular spacing controls
        $this->start_controls_section(
            'style_section_spacing',
            [
                'label' => __( 'Spacing', 'sms-login-register' ),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );
        $this->add_responsive_control(
            'field_margin_bottom',
            [
                'label' => __( 'Space Between Fields (Bottom Margin)', 'sms-login-register' ),
                'type' => Controls_Manager::SLIDER,
                'size_units' => [ 'px' ],
                'range' => [
                    'px' => [
                        'min' => 0,
                        'max' => 50,
                    ],
                ],
                'default' => [
                    'unit' => 'px',
                    'size' => 15,
                ],
                'selectors' => [
                    '{{WRAPPER}} .slr-otp-form-container .slr-form-row' => 'margin-bottom: {{SIZE}}{{UNIT}};',
                ],
            ]
        );
        $this->add_responsive_control(
            'label_margin_bottom',
            [
                'label' => __( 'Space Below Labels (Bottom Margin)', 'sms-login-register' ),
                'type' => Controls_Manager::SLIDER,
                'size_units' => [ 'px' ],
                'range' => [ 'px' => [ 'min' => 0, 'max' => 30 ] ],
                'default' => [ 'unit' => 'px', 'size' => 5 ],
                'selectors' => [ '{{WRAPPER}} .slr-otp-form-container label' => 'margin-bottom: {{SIZE}}{{UNIT}};' ],
                'condition' => [ 'show_labels' => 'yes' ], // Only if labels are shown
            ]
        );
        $this->end_controls_section();
        
        // Expand controls for Labels (Typography, Color)
        $this->start_controls_section(
            'style_section_labels',
            [
                'label' => __( 'Labels', 'sms-login-register' ),
                'tab' => Controls_Manager::TAB_STYLE,
                'condition' => [ 'show_labels' => 'yes' ],
            ]
        );
        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            [
                'name' => 'label_typography',
                'selector' => '{{WRAPPER}} .slr-otp-form-container label',
            ]
        );
        $this->add_control(
            'label_color',
            [
                'label' => __( 'Color', 'sms-login-register' ),
                'type' => Controls_Manager::COLOR,
                'selectors' => [ '{{WRAPPER}} .slr-otp-form-container label' => 'color: {{VALUE}};' ],
            ]
        );
        $this->end_controls_section();
    }

    protected function render() {
        $settings = $this->get_settings_for_display();
        
        // This part for getting public class instance needs a robust solution.
        // For now, assuming it's handled or we create a temporary one.
        $public_class_instance = null;
        if (class_exists('Sms_Login_Register_Public')) {
            $plugin_name = defined('SLR_PLUGIN_NAME_FOR_INSTANCE') ? SLR_PLUGIN_NAME_FOR_INSTANCE : 'sms-login-register';
            $plugin_version = defined('SLR_PLUGIN_VERSION_FOR_INSTANCE') ? SLR_PLUGIN_VERSION_FOR_INSTANCE : '1.0.4';
            $public_class_instance = new \Sms_Login_Register_Public($plugin_name, $plugin_version);
        }

        if ($public_class_instance) {
            $button_texts = [
                'send_otp' => $settings['text_send_otp'],
                'submit'   => $settings['text_submit'],
                'google'   => $settings['text_google'],
            ];
            $form_args = [
                'context'     => $settings['form_context'],
                'show_labels' => ($settings['show_labels'] === 'yes'),
                'redirect_to' => isset($settings['redirect_to']['url']) ? $settings['redirect_to']['url'] : '',
                'theme'       => $settings['form_theme'],
                'layout'      => $settings['form_layout'], // Use layout from Elementor settings
                'button_texts'=> $button_texts,
            ];
            echo $public_class_instance->get_otp_form_html( $form_args );
        } else {
            echo __('Error: SMS Login Register Public class not found or could not be instantiated.', 'sms-login-register');
        }
    
    }

    protected function content_template() {
        // Used for Elementor editor live preview (JavaScript-based).
        // For complex forms, this can be harder. Often, a placeholder is shown in the editor.
        ?>
        <#
        var form_id = 'slr-otp-form-elementor-' + Math.random().toString(36).substring(7);
        var show_labels = (settings.show_labels === 'yes');
        #>
        <div class="slr-otp-form-container elementor-widget-slr-otp-form-placeholder">
             <p><strong><?php _e('SMS/OTP Login Form Placeholder', 'sms-login-register'); ?></strong></p>
            <p><?php _e('Context:', 'sms-login-register'); ?> {{{ settings.form_context }}}</p>
            <p><?php _e('Show Labels:', 'sms-login-register'); ?> {{{ settings.show_labels }}}</p>
            <# if (settings.redirect_to && settings.redirect_to.url) { #>
            <p><?php _e('Redirect To:', 'sms-login-register'); ?> {{{ settings.redirect_to.url }}}</p>
            <# } #>
            <p><em><?php _e('Actual form will be rendered on the frontend.', 'sms-login-register'); ?></em></p>
        </div>
        <?php
    }
}