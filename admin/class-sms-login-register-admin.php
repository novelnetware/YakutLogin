<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://example.com/
 * @since      1.0.0
 *
 * @package    Sms_Login_Register
 * @subpackage Sms_Login_Register/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for enqueueing
 * the admin-specific stylesheet and JavaScript.
 *
 * @package    Sms_Login_Register
 * @subpackage Sms_Login_Register/admin
 * @author     Your Name <email@example.com>
 */
class Sms_Login_Register_Admin {

    /**
     * The ID of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $plugin_name    The ID of this plugin.
     */
    private $plugin_name;

    /**
     * The version of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $version    The current version of this plugin.
     */
    private $version;

    private $gateway_manager;

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     * @param    string    $plugin_name       The name of this plugin.
     * @param    string    $version    The version of this plugin.
     */
    public function __construct( $plugin_name, $version ) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
        // Get the gateway manager instance from the main plugin if possible
        // For now, instantiate directly or assume it's available via a global/helper.
        if (class_exists('SLR_Gateway_Manager')) {
            $this->gateway_manager = new SLR_Gateway_Manager();
        } else {
            // Handle error: Gateway Manager class not found
            $this->gateway_manager = null;
        }
    }

    /**
     * Register the stylesheets for the admin area.
     *
     * @since    1.0.0
     * @param string $hook The current admin page.
     */
    public function enqueue_styles( $hook ) {
        /**
         * An example of enqueueing a stylesheet for a specific admin page.
         *
         * The $hook parameter is the hook suffix for the current admin page.
         * For example, if your plugin adds a top-level menu page 'slr-settings',
         * the hook suffix will be 'toplevel_page_slr-settings'.
         * You can use this to load styles/scripts only on your plugin's pages.
         */
        // if ( 'toplevel_page_slr-main-menu' !== $hook ) {
        //     return;
        // }
        // wp_enqueue_style( $this->plugin_name, SLR_PLUGIN_URL . 'admin/css/sms-login-register-admin.css', array(), $this->version, 'all' );
    }

    /**
     * Register the JavaScript for the admin area.
     *
     * @since    1.0.0
     * @param string $hook The current admin page.
     */
    public function enqueue_scripts( $hook ) {
        /**
         * An example of enqueueing JavaScript for a specific admin page.
         */
        // if ( 'toplevel_page_slr-main-menu' !== $hook ) {
        //    return;
        // }
        // wp_enqueue_script( $this->plugin_name, SLR_PLUGIN_URL . 'admin/js/sms-login-register-admin.js', array( 'jquery' ), $this->version, false );
    }

    /**
     * Add an options page under "Settings".
     * We will create a top-level menu later if needed.
     * For now, a sub-menu under Settings is standard for many plugins.
     */
    public function add_plugin_admin_menu() {
        // Add a new top-level menu (this is what we'll use)
        add_menu_page(
            __( 'SMS Login Settings', 'sms-login-register' ), // Page title
            __( 'SMS Login', 'sms-login-register' ),          // Menu title
            'manage_options',                                 // Capability
            $this->plugin_name . '-settings',                 // Menu slug
            array( $this, 'display_plugin_setup_page' ),      // Function to display the page
            'dashicons-smartphone',                           // Icon URL
            75                                                // Position
        );

        // Example: Add a sub-menu page
        /*
        add_submenu_page(
            $this->plugin_name . '-settings',                 // Parent slug
            __( 'General Settings', 'sms-login-register' ),   // Page title
            __( 'General', 'sms-login-register' ),            // Menu title
            'manage_options',                                 // Capability
            $this->plugin_name . '-general',                  // Menu slug
            array( $this, 'display_general_settings_page' )   // Function
        );
        */
    }

    /**
     * Render the settings page for this plugin.
     *
     * @since    1.0.0
     */
    public function display_plugin_setup_page() {
        // We will create the actual settings page structure in a separate file for clarity.
        // For now, a simple placeholder.
        require_once SLR_PLUGIN_DIR . 'admin/partials/sms-login-register-admin-display.php';
    }

    /**
     * (Optional) Callback for a sub-menu page example
     */
    // public function display_general_settings_page() {
    //    echo '<h1>' . esc_html__( 'General Settings Sub-Page', 'sms-login-register' ) . '</h1>';
    // }


    /**
     * Register the settings for this plugin.
     * This function will be hooked to 'admin_init'.
     *
     * @since    1.0.0
     */
    public function register_settings() {
        // Register our settings group
        // 'slr_option_group' is the name of the group.
        // 'slr_plugin_options' is the name of the option in the wp_options table.
        register_setting(
            'slr_option_group', // Option group
            'slr_plugin_options', // Option name
            array( $this, 'sanitize_settings' ) // Sanitize callback
        );

        // Add settings sections
        // add_settings_section( $id, $title, $callback, $page );
        add_settings_section(
            'slr_general_section', // ID
            __( 'General Settings', 'sms-login-register' ), // Title
            array( $this, 'print_general_section_info' ), // Callback
            $this->plugin_name . '-settings' // Page slug (same as add_menu_page slug)
        );

        // Add settings fields
        // add_settings_field( $id, $title, $callback, $page, $section, $args );
        add_settings_field(
            'sms_provider', // ID
            __( 'SMS Provider', 'sms-login-register' ), // Title
            array( $this, 'sms_provider_callback' ), // Callback
            $this->plugin_name . '-settings', // Page
            'slr_general_section' // Section
        );

        register_setting(
            'slr_option_group', // Option group
            'slr_plugin_options', // Option name
            array( $this, 'sanitize_settings' ) // Sanitize callback
        );

        // General Settings Section (already exists)
        add_settings_section(
            'slr_general_section',
            __( 'General Settings', 'sms-login-register' ),
            array( $this, 'print_general_section_info' ),
            $this->plugin_name . '-settings'
        );
        // General fields (sms_provider, email_otp_enabled, etc. - already added)
         add_settings_field( // Make sure sms_provider is still here or moved appropriately
            'sms_provider',
            __( 'SMS Provider', 'sms-login-register' ),
            array( $this, 'sms_provider_callback' ),
            $this->plugin_name . '-settings',
            'slr_general_section'
        );
         add_settings_field(
            'sms_otp_template',
            __( 'SMS OTP Message Template', 'sms-login-register' ),
            array( $this, 'sms_otp_template_callback' ),
            $this->plugin_name . '-settings',
            'slr_general_section'
        );

        add_settings_field(
            'email_otp_enabled',
            __( 'Enable Email OTP', 'sms-login-register' ),
            array( $this, 'email_otp_enabled_callback' ),
            $this->plugin_name . '-settings',
            'slr_general_section'
        );

        add_settings_field(
            'otp_email_subject',
            __( 'OTP Email Subject', 'sms-login-register' ),
            array( $this, 'otp_email_subject_callback' ),
            $this->plugin_name . '-settings',
            'slr_general_section'
        );

        add_settings_field(
            'otp_email_body',
            __( 'OTP Email Body', 'sms-login-register' ),
            array( $this, 'otp_email_body_callback' ),
            $this->plugin_name . '-settings',
            'slr_general_section'
        );
        add_settings_field(
            'wc_checkout_otp_integration',
            __( 'WooCommerce Checkout OTP', 'sms-login-register' ),
            array( $this, 'wc_checkout_otp_integration_callback' ),
            $this->plugin_name . '-settings',
            'slr_general_section'
        );

        // Gateway Specific Settings Section
        if ($this->gateway_manager) {
            $active_gateway = $this->gateway_manager->get_active_gateway();
            if ( $active_gateway ) {
                $gateway_settings_fields = $active_gateway->get_settings_fields();
                if ( ! empty( $gateway_settings_fields ) ) {
                    add_settings_section(
                        'slr_gateway_specific_section_' . $active_gateway->get_id(),
                        sprintf( __( '%s Settings', 'sms-login-register' ), $active_gateway->get_name() ),
                        null, // No description callback needed for this section for now
                        $this->plugin_name . '-settings'
                    );

                    foreach ( $gateway_settings_fields as $field_id => $field_args ) {
                        add_settings_field(
                            $field_id,
                            esc_html( $field_args['label'] ),
                            array( $this, 'render_gateway_setting_field' ),
                            $this->plugin_name . '-settings',
                            'slr_gateway_specific_section_' . $active_gateway->get_id(),
                            array(
                                'id' => $field_id,
                                'type' => isset($field_args['type']) ? $field_args['type'] : 'text',
                                'options' => isset($field_args['options']) ? $field_args['options'] : [], // For select/radio
                                'desc' => isset($field_args['desc']) ? $field_args['desc'] : '',
                                'default' => isset($field_args['default']) ? $field_args['default'] : '',
                            )
                        );
                    }
                }
            }
        }


    }

    /**
     * Renders a generic gateway setting field.
     */
    public function render_gateway_setting_field( $args ) {
        $options = get_option( 'slr_plugin_options' );
        $value = isset( $options[$args['id']] ) ? $options[$args['id']] : (isset($args['default']) ? $args['default'] : '');

        switch ( $args['type'] ) {
            case 'text':
            case 'password': // Passwords should be handled with care, consider 'password' input type
                printf(
                    '<input type="%s" id="%s" name="slr_plugin_options[%s]" value="%s" class="regular-text" />',
                    esc_attr( $args['type'] ),
                    esc_attr( $args['id'] ),
                    esc_attr( $args['id'] ),
                    esc_attr( $value )
                );
                break;
            case 'checkbox':
                printf(
                    '<input type="checkbox" id="%s" name="slr_plugin_options[%s]" value="1" %s />',
                    esc_attr( $args['id'] ),
                    esc_attr( $args['id'] ),
                    checked( 1, $value, false )
                );
                break;
            // Add more types like 'select', 'textarea' if needed by gateways
        }
        if (!empty($args['desc'])) {
            echo '<p class="description">' . esc_html($args['desc']) . '</p>';
        }
    }
    add_settings_field(
        'google_login_enabled',
        __( 'Enable Google Login', 'sms-login-register' ),
        array( $this, 'google_login_enabled_callback' ),
        $this->plugin_name . '-settings',
        'slr_general_section'
    );

    add_settings_field(
        'google_client_id',
        __( 'Google Client ID', 'sms-login-register' ),
        array( $this, 'google_client_id_callback' ),
        $this->plugin_name . '-settings',
        'slr_general_section'
    );

    add_settings_field(
        'google_client_secret',
        __( 'Google Client Secret', 'sms-login-register' ),
        array( $this, 'google_client_secret_callback' ),
        $this->plugin_name . '-settings',
        'slr_general_section'
    );

    add_settings_field(
        'google_redirect_uri_display',
        __( 'Your Google Redirect URI', 'sms-login-register' ),
        array( $this, 'google_redirect_uri_display_callback' ),
        $this->plugin_name . '-settings',
        'slr_general_section'
    );

    
    add_settings_field(
        'captcha_type',
        __( 'CAPTCHA Type', 'sms-login-register' ),
        array( $this, 'captcha_type_callback' ),
        $this->plugin_name . '-settings',
        'slr_general_section' // Or a new section for Security
    );

    // Google reCAPTCHA v2 Settings (conditionally shown by JS or always present and user fills if selected)
    add_settings_field(
        'recaptcha_v2_site_key',
        __( 'Google reCAPTCHA v2 Site Key', 'sms-login-register' ),
        array( $this, 'recaptcha_v2_site_key_callback' ),
        $this->plugin_name . '-settings',
        'slr_general_section' // Or specific reCAPTCHA section
    );
    add_settings_field(
        'recaptcha_v2_secret_key',
        __( 'Google reCAPTCHA v2 Secret Key', 'sms-login-register' ),
        array( $this, 'recaptcha_v2_secret_key_callback' ),
        $this->plugin_name . '-settings',
        'slr_general_section'
    );

    // Cloudflare Turnstile Settings
    add_settings_field(
        'turnstile_site_key',
        __( 'Cloudflare Turnstile Site Key', 'sms-login-register' ),
        array( $this, 'turnstile_site_key_callback' ),
        $this->plugin_name . '-settings',
        'slr_general_section' // Or specific Turnstile section
    );
    add_settings_field(
        'turnstile_secret_key',
        __( 'Cloudflare Turnstile Secret Key', 'sms-login-register' ),
        array( $this, 'turnstile_secret_key_callback' ),
        $this->plugin_name . '-settings',
        'slr_general_section'
    );
        // Add more fields for Google Login, reCAPTCHA etc. here later
    }

    /**
     * Sanitize each setting field as needed.
     *
     * @param array $input Contains all settings fields as array keys
     * @return array Sanitized input
     * @since    1.0.0
     */
    public function sanitize_settings( $input ) {
        $sanitized_input = array();

        if ( isset( $input['sms_provider'] ) ) {
            $sanitized_input['sms_provider'] = sanitize_text_field( $input['sms_provider'] );
        }

        if ( isset( $input['email_otp_enabled'] ) ) {
            $sanitized_input['email_otp_enabled'] = (bool) $input['email_otp_enabled'];
        } else {
            $sanitized_input['email_otp_enabled'] = false;
        }

        public function otp_email_subject_callback() {
            $options = get_option( 'slr_plugin_options' );
            $subject = isset( $options['otp_email_subject'] ) ? $options['otp_email_subject'] : __( 'Your One-Time Password', 'sms-login-register' );
            printf(
                '<input type="text" class="regular-text" id="otp_email_subject" name="slr_plugin_options[otp_email_subject]" value="%s" />',
                esc_attr( $subject )
            );
            echo '<p class="description">' . __('Subject for the OTP email. Use {otp_code} for the OTP.', 'sms-login-register') . '</p>';
        }
    
        public function otp_email_body_callback() {
            $options = get_option( 'slr_plugin_options' );
            $body = isset( $options['otp_email_body'] ) ? $options['otp_email_body'] : __( "Your OTP code is: {otp_code}\nThis code is valid for 5 minutes.", 'sms-login-register' );
            printf(
                '<textarea id="otp_email_body" name="slr_plugin_options[otp_email_body]" rows="5" class="large-text">%s</textarea>',
                esc_textarea( $body )
            );
            echo '<p class="description">' . __('Body for the OTP email. Use {otp_code} for the OTP, {site_title} for your site title, and {site_url} for your site URL.', 'sms-login-register') . '</p>';
        }
    
        // به‌روزرسانی متد sanitize_settings
        public function sanitize_settings( $input ) {
            $current_options = get_option('slr_plugin_options', []);
        $new_input = array_merge($current_options, $input);
        $output = [];

        // General settings
        $output['sms_provider'] = isset($new_input['sms_provider']) ? sanitize_text_field($new_input['sms_provider']) : '';
        $output['email_otp_enabled'] = isset($new_input['email_otp_enabled']);
        $output['otp_email_subject'] = isset($new_input['otp_email_subject']) ? sanitize_text_field($new_input['otp_email_subject']) : '';
        $output['otp_email_body'] = isset($new_input['otp_email_body']) ? wp_kses_post($new_input['otp_email_body']) : '';
        $output['sms_otp_template'] = isset($new_input['sms_otp_template']) ? sanitize_text_field($new_input['sms_otp_template']) : '';
        $output['wc_checkout_otp_integration'] = isset($new_input['wc_checkout_otp_integration']);

        // Sanitize gateway-specific settings
        if ($this->gateway_manager) {
            $active_gateway_id = $output['sms_provider']; // Use the newly selected provider
            if ( $active_gateway_id ) {
                 $all_gateways = $this->gateway_manager->get_available_gateways();
                 if(isset($all_gateways[$active_gateway_id])) {
                    $active_gateway = $all_gateways[$active_gateway_id];
                    $gateway_fields = $active_gateway->get_settings_fields();
                    foreach ( $gateway_fields as $field_id => $field_args ) {
                        if ( isset( $new_input[$field_id] ) ) {
                            if ( $field_args['type'] === 'checkbox' ) {
                                $output[$field_id] = true; // Checkbox is present means it's checked
                            } else {
                                $output[$field_id] = sanitize_text_field( $new_input[$field_id] );
                            }
                        } else {
                            if ( $field_args['type'] === 'checkbox' ) {
                                $output[$field_id] = false; // Checkbox not present means unchecked
                            }
                            // For other types, if not set, they won't be in $output unless a default is handled
                        }
                    }
                 }
            }
        }

        

        $output['google_login_enabled'] = isset($new_input['google_login_enabled']);
    $output['google_client_id'] = isset($new_input['google_client_id']) ? sanitize_text_field($new_input['google_client_id']) : '';
    $output['google_client_secret'] = isset($new_input['google_client_secret']) ? sanitize_text_field($new_input['google_client_secret']) : ''; // Keep it as text, no special sanitization for secrets other than escaping




    $output['captcha_type'] = isset($new_input['captcha_type']) ? sanitize_text_field($new_input['captcha_type']) : 'none';
    $output['recaptcha_v2_site_key'] = isset($new_input['recaptcha_v2_site_key']) ? sanitize_text_field($new_input['recaptcha_v2_site_key']) : '';
    $output['recaptcha_v2_secret_key'] = isset($new_input['recaptcha_v2_secret_key']) ? sanitize_text_field($new_input['recaptcha_v2_secret_key']) : '';
    $output['turnstile_site_key'] = isset($new_input['turnstile_site_key']) ? sanitize_text_field($new_input['turnstile_site_key']) : '';
    $output['turnstile_secret_key'] = isset($new_input['turnstile_secret_key']) ? sanitize_text_field($new_input['turnstile_secret_key']) : '';


        return $output;
    }

    /**
     * Print the Section Text
     * @since    1.0.0
     */
    public function print_general_section_info() {
        print __( 'Configure the general settings for the SMS Login & Register plugin:', 'sms-login-register' );
    }

    /**
     * Get the settings option array and print one of its values
     * @since    1.0.0
     */
    public function sms_provider_callback() {
        $options = get_option( 'slr_plugin_options' );
        $current_provider_id = isset( $options['sms_provider'] ) ? $options['sms_provider'] : '';
        
        echo '<select id="sms_provider" name="slr_plugin_options[sms_provider]">';
        echo '<option value="">' . esc_html__( '-- Select a Provider --', 'sms-login-register' ) . '</option>';

        if ($this->gateway_manager) {
            $available_gateways = $this->gateway_manager->get_available_gateways();
            foreach ( $available_gateways as $gateway_id => $gateway_instance ) {
                echo '<option value="' . esc_attr( $gateway_id ) . '" ' . selected( $current_provider_id, $gateway_id, false ) . '>' . esc_html( $gateway_instance->get_name() ) . '</option>';
            }
        }
        echo '</select>';
        echo '<p class="description">' . __('Select your SMS provider. Specific settings will appear below once saved.', 'sms-login-register') . '</p>';
    }

    public function email_otp_enabled_callback() {
        $options = get_option( 'slr_plugin_options' );
        $checked = isset( $options['email_otp_enabled'] ) && $options['email_otp_enabled'] ? 'checked' : '';
        printf(
            '<input type="checkbox" id="email_otp_enabled" name="slr_plugin_options[email_otp_enabled]" value="1" %s />',
            $checked
        );
        echo '<label for="email_otp_enabled"> ' . __('Enable sending OTP via Email as an alternative.', 'sms-login-register') . '</label>';
    }
    public function google_login_enabled_callback() {
        $options = get_option( 'slr_plugin_options' );
        $checked = isset( $options['google_login_enabled'] ) && $options['google_login_enabled'] ? 'checked' : '';
        printf(
            '<input type="checkbox" id="google_login_enabled" name="slr_plugin_options[google_login_enabled]" value="1" %s />',
            $checked
        );
        echo '<label for="google_login_enabled"> ' . __('Allow users to log in or register using their Google account.', 'sms-login-register') . '</label>';
    }

    public function google_client_id_callback() {
        $options = get_option( 'slr_plugin_options' );
        $client_id = isset( $options['google_client_id'] ) ? $options['google_client_id'] : '';
        printf(
            '<input type="text" class="regular-text" id="google_client_id" name="slr_plugin_options[google_client_id]" value="%s" />',
            esc_attr( $client_id )
        );
    }

    public function google_client_secret_callback() {
        $options = get_option( 'slr_plugin_options' );
        $client_secret = isset( $options['google_client_secret'] ) ? $options['google_client_secret'] : '';
        printf(
            '<input type="password" class="regular-text" id="google_client_secret" name="slr_plugin_options[google_client_secret]" value="%s" />', // type="password" for secret
            esc_attr( $client_secret )
        );
    }

    public function google_redirect_uri_display_callback() {
        // Construct the redirect URI based on the site's home_url
        // This must exactly match what's entered in Google Cloud Console
        $redirect_uri = add_query_arg( 'slr_google_auth_callback', '1', home_url( '/' ) );
        echo '<code>' . esc_url( $redirect_uri ) . '</code>';
        echo '<p class="description">' . __( 'Copy this URI and paste it into your Google Cloud Console project under "Authorized redirect URIs".', 'sms-login-register') . '</p>';
    }
    public function captcha_type_callback() {
        $options = get_option( 'slr_plugin_options' );
        $current_type = isset( $options['captcha_type'] ) ? $options['captcha_type'] : 'none';
        $captcha_types = array(
            'none' => __( 'None', 'sms-login-register' ),
            'recaptcha_v2' => __( 'Google reCAPTCHA v2 ("I\'m not a robot")', 'sms-login-register' ),
            'turnstile' => __( 'Cloudflare Turnstile', 'sms-login-register' ),
        );

        echo '<select id="captcha_type" name="slr_plugin_options[captcha_type]">';
        foreach ($captcha_types as $key => $label) {
            echo '<option value="' . esc_attr($key) . '" ' . selected($current_type, $key, false) . '>' . esc_html($label) . '</option>';
        }
        echo '</select>';
        echo '<p class="description">' . __('Select the CAPTCHA service to use on forms.', 'sms-login-register') . '</p>';
        // You might add JavaScript here to show/hide relevant Site Key/Secret Key fields based on selection.
    }

    public function recaptcha_v2_site_key_callback() {
        $options = get_option( 'slr_plugin_options' );
        $value = isset( $options['recaptcha_v2_site_key'] ) ? $options['recaptcha_v2_site_key'] : '';
        printf( '<input type="text" class="regular-text" id="recaptcha_v2_site_key" name="slr_plugin_options[recaptcha_v2_site_key]" value="%s" />', esc_attr( $value ) );
        echo '<p class="description">' . __('Required if Google reCAPTCHA v2 is selected.', 'sms-login-register') . '</p>';
    }

    public function recaptcha_v2_secret_key_callback() {
        $options = get_option( 'slr_plugin_options' );
        $value = isset( $options['recaptcha_v2_secret_key'] ) ? $options['recaptcha_v2_secret_key'] : '';
        printf( '<input type="password" class="regular-text" id="recaptcha_v2_secret_key" name="slr_plugin_options[recaptcha_v2_secret_key]" value="%s" />', esc_attr( $value ) );
         echo '<p class="description">' . __('Required if Google reCAPTCHA v2 is selected.', 'sms-login-register') . '</p>';
    }

    public function turnstile_site_key_callback() {
        $options = get_option( 'slr_plugin_options' );
        $value = isset( $options['turnstile_site_key'] ) ? $options['turnstile_site_key'] : '';
        printf( '<input type="text" class="regular-text" id="turnstile_site_key" name="slr_plugin_options[turnstile_site_key]" value="%s" />', esc_attr( $value ) );
        echo '<p class="description">' . __('Required if Cloudflare Turnstile is selected.', 'sms-login-register') . '</p>';
    }

    public function turnstile_secret_key_callback() {
        $options = get_option( 'slr_plugin_options' );
        $value = isset( $options['turnstile_secret_key'] ) ? $options['turnstile_secret_key'] : '';
        printf( '<input type="password" class="regular-text" id="turnstile_secret_key" name="slr_plugin_options[turnstile_secret_key]" value="%s" />', esc_attr( $value ) );
        echo '<p class="description">' . __('Required if Cloudflare Turnstile is selected.', 'sms-login-register') . '</p>';
    }
    public function wc_checkout_otp_integration_callback() {
        $options = get_option( 'slr_plugin_options' );
        $checked = isset( $options['wc_checkout_otp_integration'] ) && $options['wc_checkout_otp_integration'] ? 'checked' : '';

        if ( ! class_exists( 'WooCommerce' ) ) {
            echo '<p class="description">' . __( 'WooCommerce plugin is not active. This feature requires WooCommerce.', 'sms-login-register' ) . '</p>';
            printf( '<input type="checkbox" id="wc_checkout_otp_integration" name="slr_plugin_options[wc_checkout_otp_integration]" value="1" %s disabled />', $checked );
        } else {
            printf( '<input type="checkbox" id="wc_checkout_otp_integration" name="slr_plugin_options[wc_checkout_otp_integration]" value="1" %s />', $checked );
        }
        echo '<label for="wc_checkout_otp_integration"> ' . __('Replace default WooCommerce login & registration forms on the checkout page with OTP form.', 'sms-login-register') . '</label>';
        echo '<p class="description">' . __('This will prompt users to log in or register via OTP before proceeding with checkout details.', 'sms-login-register') . '</p>';
    }
}