<?php
/**
 * Handles WooCommerce Integration for OTP Login/Registration on Checkout.
 *
 * @link       https://yakut.ir/
 * @since      1.0.4
 *
 * @package    Sms_Login_Register
 * @subpackage Sms_Login_Register/includes/integrations
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

class SLR_WooCommerce_Integration {

    private $slr_public_instance; // Instance of Sms_Login_Register_Public

    public function __construct( $slr_public_instance ) {
        $this->slr_public_instance = $slr_public_instance;
        $options = get_option( 'slr_plugin_options' );

        if ( isset( $options['wc_checkout_otp_integration'] ) && $options['wc_checkout_otp_integration'] ) {
            // Remove default WooCommerce login form and registration prompts from checkout
            add_action( 'woocommerce_before_checkout_form', array( $this, 'remove_checkout_login_form'), 9 );
            add_filter( 'woocommerce_checkout_registration_enabled', array( $this, 'disable_checkout_registration_form' ) );

            // Add our custom OTP form
            add_action( 'woocommerce_before_checkout_form', array( $this, 'display_otp_form_on_checkout' ), 10 );
        }
    }

    /**
     * Removes the default WooCommerce checkout login form.
     */
    public function remove_checkout_login_form() {
        // The login form is hooked to woocommerce_before_checkout_form with priority 10
        remove_action( 'woocommerce_before_checkout_form', 'woocommerce_checkout_login_form', 10 );
        
        // Remove "Returning customer?" collapsible notice if you want a cleaner replacement
        // This might require more specific targeting or JS if it's not directly removable via a single action.
        // For now, just removing the form itself.
    }

    /**
     * Disables the default WooCommerce registration fields on checkout.
     * We'll handle registration via our OTP form.
     */
    public function disable_checkout_registration_form( $enabled ) {
        // This filter controls whether the "create an account?" checkbox and fields are shown
        // when guest checkout is enabled.
        // By returning false, we prevent WC from showing its own registration elements.
        return false; 
    }


    /**
     * Displays the OTP login/registration form on the checkout page.
     */
    public function display_otp_form_on_checkout() {
        if ( is_user_logged_in() ) {
            return; // Don't show if user is already logged in
        }
        
        // Ensure scripts and styles are loaded for the OTP form
        if ( method_exists( $this->slr_public_instance, 'maybe_enqueue_scripts' ) ) {
            $this->slr_public_instance->maybe_enqueue_scripts();
        }

        echo '<div class="slr-wc-checkout-otp-wrapper">';
        // You might want to add a specific message for checkout context
        echo '<h3>' . esc_html__( 'برای ادامه، با کد یکبارمصرف وارد شوید یا ثبت‌نام کنید', 'yakutlogin' ) . '</h3>';
        
        $form_args = array(
            'form_id'     => 'slr-otp-checkout-form',
            'context'     => 'mixed', // Allows both login and registration
            'show_labels' => true,
            'theme'       => 'default', // Or a specific checkout theme
            'redirect_to' => wc_get_checkout_url() // Redirect back to checkout after AJAX login/reg
        );
        echo $this->slr_public_instance->get_otp_form_html( $form_args );
        echo '</div>';
        
        // Add some JS for handling AJAX success on checkout if needed
        // For example, to reload the checkout page or update fragments.
        // The AJAX handler `ajax_process_login_register_otp` already supports `redirect_url`.
        // Sending `wc_get_checkout_url()` should reload the page and reflect the logged-in state.
    }
}