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

class Sms_Login_Register_Admin {

    private $plugin_name;
    private $version;
    private $gateway_manager;

    public function __construct( $plugin_name, $version ) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
        if (class_exists('SLR_Gateway_Manager')) {
            $this->gateway_manager = new SLR_Gateway_Manager();
        } else {
            $this->gateway_manager = null;
        }
        add_action( 'wp_ajax_yakutlogin_save_settings', array( $this, 'ajax_save_settings' ) );
        add_action( 'wp_ajax_yakutlogin_get_gateway_fields', array( $this, 'ajax_get_gateway_fields' ) );
    }

     /**
     * هندلر ایجکس برای ذخیره تنظیمات
     */
    public function ajax_save_settings() {
        check_ajax_referer( 'yakutlogin_admin_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( [ 'message' => 'دسترسی غیرمجاز' ] );
        }

        if ( isset( $_POST['settings'] ) ) {
            parse_str( $_POST['settings'], $form_data );
            $sanitized_data = $this->sanitize_settings( $form_data );
            update_option( 'slr_plugin_options', $sanitized_data );
            wp_send_json_success( [ 'message' => 'تنظیمات با موفقیت ذخیره شد!' ] );
        } else {
            wp_send_json_error( [ 'message' => 'اطلاعاتی برای ذخیره ارسال نشده است.' ] );
        }
    }

    /**
     * هندلر ایجکس برای دریافت فیلدهای درگاه پیامک
     */
    public function ajax_get_gateway_fields() {
        check_ajax_referer( 'yakutlogin_admin_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error();
        }

        $gateway_id = isset( $_POST['gateway_id'] ) ? sanitize_key( $_POST['gateway_id'] ) : '';
        if ( empty( $gateway_id ) ) {
            wp_send_json_success( [ 'html' => '' ] );
        }
        
        $gateway = $this->gateway_manager->get_available_gateways()[$gateway_id] ?? null;
        if (!$gateway) {
            wp_send_json_error();
        }

        $options = get_option('slr_plugin_options', []);
        $fields_html = '';
        
        ob_start();
        echo '<h3>تنظیمات ' . esc_html($gateway->get_name()) . '</h3>';
        foreach ($gateway->get_settings_fields() as $field_id => $field_args) {
            echo '<div class="setting-option">';
            echo '<label>' . esc_html($field_args['label']) . '</label>';
            $this->render_setting_field($field_id, $field_args['type'], $options, $field_args['desc'] ?? '');
            echo '</div>';
        }
        $fields_html = ob_get_clean();

        wp_send_json_success( [ 'html' => $fields_html ] );
    }
}

  

public function enqueue_styles( $hook ) {
    // فقط در صفحه تنظیمات افزونه این فایل‌ها را بارگذاری کن
    if ( 'toplevel_page_' . $this->plugin_name . '-settings' !== $hook ) {
        return;
    }
    // فراخوانی Font Awesome از CDN
    wp_enqueue_style( 'font-awesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css', array(), '6.0.0' );
    // فراخوانی فایل CSS پنل جدید
    wp_enqueue_style( $this->plugin_name . '-admin-panel', plugin_dir_url( __FILE__ ) . 'assets/css/yakutlogin-admin-panel.css', array(), $this->version, 'all' );
}

public function enqueue_scripts( $hook ) {
    // فقط در صفحه تنظیمات افزونه این فایل‌ها را بارگذاری کن
    if ( 'toplevel_page_' . $this->plugin_name . '-settings' !== $hook ) {
        return;
    }
    // فراخوانی فایل JS پنل جدید
    wp_enqueue_script( $this->plugin_name . '-admin-panel', plugin_dir_url( __FILE__ ) . 'assets/js/yakutlogin-admin-panel.js', array( 'jquery' ), $this->version, true );

    // ارسال داده‌های لازم از PHP به JavaScript (برای ایجکس)
    wp_localize_script( $this->plugin_name . '-admin-panel', 'yakutlogin_admin_ajax', array(
        'ajax_url' => admin_url( 'admin-ajax.php' ),
        'nonce'    => wp_create_nonce( 'yakutlogin_admin_nonce' ),
    ) );
}

    public function add_plugin_admin_menu() {
        add_menu_page(
            __( 'تنظیمات ورود پیامکی', 'yakutlogin' ),
            __( 'ورود پیامکی', 'yakutlogin' ),
            'manage_options',
            $this->plugin_name . '-settings',
            array( $this, 'display_plugin_setup_page' ),
            'dashicons-smartphone',
            75
        );
    }

    public function display_plugin_setup_page() {
        require_once plugin_dir_path( __FILE__ ) . 'partials/yakutlogin-admin-panel-display.php';
    }

    public function render_setting_field( $id, $type, $options, $placeholder = '' ) {
        $value = isset( $options[$id] ) ? $options[$id] : '';

        switch ($type) {
            case 'checkbox':
                echo '<label class="switch">';
                echo '<input type="checkbox" name="' . esc_attr($id) . '" value="1" ' . checked(1, $value, false) . '>';
                echo '<span class="slider"></span>';
                echo '</label>';
                break;

            case 'text':
                echo '<input type="text" class="setting-input" name="' . esc_attr($id) . '" value="' . esc_attr($value) . '" placeholder="' . esc_attr($placeholder) . '">';
                break;
            
            case 'textarea':
                echo '<textarea class="setting-textarea" name="' . esc_attr($id) . '">' . esc_textarea($value) . '</textarea>';
                break;

           case 'select_gateway':
        case 'select_gateway_backup': // هر دو از یک منطق استفاده می‌کنند
            $gateways = $this->gateway_manager->get_available_gateways();
            $select_id = ($type === 'select_gateway') ? 'primary-sms-provider-select' : 'backup-sms-provider-select';

            echo '<select name="' . esc_attr($id) . '" class="setting-select" id="' . esc_attr($select_id) . '">';
            echo '<option value="">-- غیرفعال --</option>'; // تغییر به غیرفعال
            foreach ($gateways as $gateway_id => $gateway) {
                // درگاه پشتیبان نمی‌تواند با درگاه اصلی یکی باشد (این منطق در JS پیاده‌سازی می‌شود)
                echo '<option value="' . esc_attr($gateway_id) . '" ' . selected($gateway_id, $value, false) . '>' . esc_html($gateway->get_name()) . '</option>';
            }
            echo '</select>';
            break;
    }
    }

    public function register_settings() {
        register_setting(
            'slr_option_group',
            'slr_plugin_options',
            array( $this, 'sanitize_settings' )
        );

        // General Settings Section
        add_settings_section(
            'slr_general_section',
            __( 'تنظیمات عمومی', 'yakutlogin' ),
            array( $this, 'print_general_section_info' ),
            $this->plugin_name . '-settings'
        );

        // Add General Fields
        add_settings_field('email_otp_enabled', __('فعال‌سازی کد با ایمیل', 'yakutlogin'), array($this, 'email_otp_enabled_callback'), $this->plugin_name . '-settings', 'slr_general_section');
        add_settings_field('otp_email_subject', __('موضوع ایمیل کد یکبارمصرف', 'yakutlogin'), array($this, 'otp_email_subject_callback'), $this->plugin_name . '-settings', 'slr_general_section');
        add_settings_field('otp_email_body', __('متن ایمیل کد یکبارمصرف', 'yakutlogin'), array($this, 'otp_email_body_callback'), $this->plugin_name . '-settings', 'slr_general_section');
        add_settings_field('google_login_enabled', __('فعال‌سازی ورود با گوگل', 'yakutlogin'), array($this, 'google_login_enabled_callback'), $this->plugin_name . '-settings', 'slr_general_section');
        add_settings_field('google_client_id', __('Google Client ID', 'yakutlogin'), array($this, 'google_client_id_callback'), $this->plugin_name . '-settings', 'slr_general_section');
        add_settings_field('google_client_secret', __('Google Client Secret', 'yakutlogin'), array($this, 'google_client_secret_callback'), $this->plugin_name . '-settings', 'slr_general_section');
        add_settings_field('google_redirect_uri_display', __('آدرس بازگشت (Redirect URI) گوگل', 'yakutlogin'), array($this, 'google_redirect_uri_display_callback'), $this->plugin_name . '-settings', 'slr_general_section');
        add_settings_field('captcha_type', __('نوع کپچا', 'yakutlogin'), array($this, 'captcha_type_callback'), $this->plugin_name . '-settings', 'slr_general_section');
        add_settings_field('recaptcha_v2_site_key', __('Site Key گوگل ریکپچا (v2)', 'yakutlogin'), array($this, 'recaptcha_v2_site_key_callback'), $this->plugin_name . '-settings', 'slr_general_section');
        add_settings_field('recaptcha_v2_secret_key', __('Secret Key گوگل ریکپچا (v2)', 'yakutlogin'), array($this, 'recaptcha_v2_secret_key_callback'), $this->plugin_name . '-settings', 'slr_general_section');
        add_settings_field('turnstile_site_key', __('Site Key کلادفلر (Turnstile)', 'yakutlogin'), array($this, 'turnstile_site_key_callback'), $this->plugin_name . '-settings', 'slr_general_section');
        add_settings_field('turnstile_secret_key', __('Secret Key کلادفلر (Turnstile)', 'yakutlogin'), array($this, 'turnstile_secret_key_callback'), $this->plugin_name . '-settings', 'slr_general_section');
        
        if (class_exists('WooCommerce')) {
            add_settings_field('wc_checkout_otp_integration', __('جایگزینی فرم تسویه حساب ووکامرس', 'yakutlogin'), array($this, 'wc_checkout_otp_integration_callback'), $this->plugin_name . '-settings', 'slr_general_section');
        }

        // SMS Gateway Section
        add_settings_section(
            'slr_sms_gateway_section',
            __( 'تنظیمات درگاه پیامک', 'yakutlogin' ),
            array( $this, 'print_sms_gateway_section_info' ),
            $this->plugin_name . '-settings'
        );
        add_settings_field('sms_provider', __('سرویس‌دهنده پیامک', 'yakutlogin'), array($this, 'sms_provider_callback'), $this->plugin_name . '-settings', 'slr_sms_gateway_section');
        add_settings_field('sms_otp_template', __('قالب پیامک کد یکبارمصرف', 'yakutlogin'), array($this, 'sms_otp_template_callback'), $this->plugin_name . '-settings', 'slr_sms_gateway_section');

        // Gateway Specific Settings Section(s)
        if ($this->gateway_manager) {
            $options = get_option('slr_plugin_options');
            $active_gateway_id = $options['sms_provider'] ?? '';
            
            if ($active_gateway_id && isset($this->gateway_manager->get_available_gateways()[$active_gateway_id])) {
                $active_gateway = $this->gateway_manager->get_available_gateways()[$active_gateway_id];
                $gateway_settings_fields = $active_gateway->get_settings_fields();
                
                if (!empty($gateway_settings_fields)) {
                    $section_id = 'slr_gateway_specific_section_' . $active_gateway->get_id();
                    add_settings_section(
                        $section_id,
                        sprintf(__('تنظیمات %s', 'yakutlogin'), $active_gateway->get_name()),
                        null,
                        $this->plugin_name . '-settings'
                    );

                    foreach ($gateway_settings_fields as $field_id => $field_args) {
                        add_settings_field(
                            $field_id,
                            esc_html($field_args['label']),
                            array($this, 'render_gateway_setting_field'),
                            $this->plugin_name . '-settings',
                            $section_id,
                            [
                                'id' => $field_id,
                                'type' => $field_args['type'] ?? 'text',
                                'desc' => $field_args['desc'] ?? '',
                                'default' => $field_args['default'] ?? ''
                            ]
                        );
                    }
                }
            }
        }
    }

    public function sanitize_settings( $input ) {
        $sanitized_input = get_option('slr_plugin_options', array());

        $fields_to_sanitize = [
            'email_otp_enabled' => 'bool', 'otp_email_subject' => 'text', 'otp_email_body' => 'textarea',
            'google_login_enabled' => 'bool', 'google_client_id' => 'text', 'google_client_secret' => 'text',
            'captcha_type' => 'text', 'recaptcha_v2_site_key' => 'text', 'recaptcha_v2_secret_key' => 'text',
            'turnstile_site_key' => 'text', 'turnstile_secret_key' => 'text',
            'wc_checkout_otp_integration' => 'bool', 'sms_provider' => 'text', 'sms_otp_template' => 'text','sms_provider_backup' => 'text',
        ];

        if ($this->gateway_manager) {
            foreach ($this->gateway_manager->get_available_gateways() as $gateway) {
                foreach ($gateway->get_settings_fields() as $field_id => $field_args) {
                    $fields_to_sanitize[$field_id] = $field_args['type'] ?? 'text';
                }
            }
        }

        foreach ($fields_to_sanitize as $key => $type) {
            if (!isset($input[$key])) {
                if ($type === 'bool' || $type === 'checkbox') {
                    $sanitized_input[$key] = false;
                }
            } else {
                switch ($type) {
                    case 'bool': case 'checkbox':
                        $sanitized_input[$key] = true;
                        break;
                    case 'textarea':
                        $sanitized_input[$key] = wp_kses_post($input[$key]);
                        break;
                    case 'text': case 'password': default:
                        $sanitized_input[$key] = sanitize_text_field($input[$key]);
                        break;
                }
            }
        }
        return $sanitized_input;
    }

    public function print_general_section_info() {
        print __( 'تنظیمات عمومی افزونه ورود و عضویت پیامکی را از این بخش مدیریت کنید.', 'yakutlogin' );
    }
    
    public function print_sms_gateway_section_info() {
        print __( 'سرویس‌دهنده پیامک و تنظیمات مربوط به آن را انتخاب کنید. پس از ذخیره، فیلدهای مخصوص درگاه انتخابی نمایش داده می‌شود.', 'yakutlogin' );
    }

    public function email_otp_enabled_callback() {
        $options = get_option('slr_plugin_options');
        $checked = isset($options['email_otp_enabled']) && $options['email_otp_enabled'];
        echo '<input type="checkbox" id="email_otp_enabled" name="slr_plugin_options[email_otp_enabled]" value="1" ' . checked($checked, true, false) . ' />';
    }

    public function otp_email_subject_callback() {
        $options = get_option('slr_plugin_options');
        $value = $options['otp_email_subject'] ?? '';
        echo '<input type="text" class="regular-text" name="slr_plugin_options[otp_email_subject]" value="' . esc_attr($value) . '" />';
    }

    public function otp_email_body_callback() {
        $options = get_option('slr_plugin_options');
        $value = $options['otp_email_body'] ?? '';
        echo '<textarea name="slr_plugin_options[otp_email_body]" rows="5" class="large-text">' . esc_textarea($value) . '</textarea>';
        echo '<p class="description">' . __('می‌توانید از {otp_code}, {site_title}, و {site_url} استفاده کنید.', 'yakutlogin') . '</p>';
    }
    
    public function google_login_enabled_callback() {
        $options = get_option('slr_plugin_options');
        $checked = isset($options['google_login_enabled']) && $options['google_login_enabled'];
        echo '<input type="checkbox" id="google_login_enabled" name="slr_plugin_options[google_login_enabled]" value="1" ' . checked($checked, true, false) . ' />';
    }

    public function google_client_id_callback() {
        $options = get_option('slr_plugin_options');
        $value = $options['google_client_id'] ?? '';
        echo '<input type="text" class="regular-text" name="slr_plugin_options[google_client_id]" value="' . esc_attr($value) . '" />';
    }

    public function google_client_secret_callback() {
        $options = get_option('slr_plugin_options');
        $value = $options['google_client_secret'] ?? '';
        echo '<input type="password" class="regular-text" name="slr_plugin_options[google_client_secret]" value="' . esc_attr($value) . '" />';
    }

    public function google_redirect_uri_display_callback() {
        $redirect_uri = add_query_arg('slr_google_auth_callback', '1', home_url('/'));
        echo '<code>' . esc_url($redirect_uri) . '</code>';
        echo '<p class="description">' . __('این آدرس را در کنسول گوگل در بخش "Authorized redirect URIs" وارد کنید.', 'yakutlogin') . '</p>';
    }

    public function captcha_type_callback() {
        $options = get_option('slr_plugin_options');
        $current_type = $options['captcha_type'] ?? 'none';
        $captcha_types = ['none' => __('هیچکدام', 'yakutlogin'), 'recaptcha_v2' => __('گوگل ریکپچا (v2)', 'yakutlogin'), 'turnstile' => __('کلودفلر Turnstile', 'yakutlogin')];
        echo '<select name="slr_plugin_options[captcha_type]">';
        foreach ($captcha_types as $key => $label) {
            echo '<option value="' . esc_attr($key) . '" ' . selected($current_type, $key, false) . '>' . esc_html($label) . '</option>';
        }
        echo '</select>';
    }

    public function recaptcha_v2_site_key_callback() {
        $options = get_option('slr_plugin_options');
        $value = $options['recaptcha_v2_site_key'] ?? '';
        echo '<input type="text" class="regular-text" name="slr_plugin_options[recaptcha_v2_site_key]" value="' . esc_attr($value) . '" />';
    }

    public function recaptcha_v2_secret_key_callback() {
        $options = get_option('slr_plugin_options');
        $value = $options['recaptcha_v2_secret_key'] ?? '';
        echo '<input type="password" class="regular-text" name="slr_plugin_options[recaptcha_v2_secret_key]" value="' . esc_attr($value) . '" />';
    }

    public function turnstile_site_key_callback() {
        $options = get_option('slr_plugin_options');
        $value = $options['turnstile_site_key'] ?? '';
        echo '<input type="text" class="regular-text" name="slr_plugin_options[turnstile_site_key]" value="' . esc_attr($value) . '" />';
    }

    public function turnstile_secret_key_callback() {
        $options = get_option('slr_plugin_options');
        $value = $options['turnstile_secret_key'] ?? '';
        echo '<input type="password" class="regular-text" name="slr_plugin_options[turnstile_secret_key]" value="' . esc_attr($value) . '" />';
    }

    public function wc_checkout_otp_integration_callback() {
        $options = get_option('slr_plugin_options');
        $checked = isset($options['wc_checkout_otp_integration']) && $options['wc_checkout_otp_integration'];
        if (!class_exists('WooCommerce')) {
            echo '<p class="description">' . __('ووکامرس فعال نیست.', 'yakutlogin') . '</p>';
        } else {
            echo '<input type="checkbox" id="wc_checkout_otp_integration" name="slr_plugin_options[wc_checkout_otp_integration]" value="1" ' . checked($checked, true, false) . ' />';
        }
    }
    
    public function sms_provider_callback() {
        $options = get_option('slr_plugin_options');
        $current_provider_id = $options['sms_provider'] ?? '';
        echo '<select name="slr_plugin_options[sms_provider]">';
        echo '<option value="">' . esc_html__('-- انتخاب سرویس --', 'yakutlogin') . '</option>';
        if ($this->gateway_manager) {
            foreach ($this->gateway_manager->get_available_gateways() as $gateway_id => $gateway_instance) {
                echo '<option value="' . esc_attr($gateway_id) . '" ' . selected($current_provider_id, $gateway_id, false) . '>' . esc_html($gateway_instance->get_name()) . '</option>';
            }
        }
        echo '</select>';
    }

    public function sms_otp_template_callback() {
        $options = get_option('slr_plugin_options');
        $value = $options['sms_otp_template'] ?? '';
        echo '<input type="text" class="regular-text" name="slr_plugin_options[sms_otp_template]" value="' . esc_attr($value) . '" />';
        echo '<p class="description">' . __('از {otp_code} استفاده کنید. (توجه: برخی درگاه‌ها از پترن استفاده می‌کنند و این فیلد را نادیده می‌گیرند.)', 'yakutlogin') . '</p>';
    }

    public function render_gateway_setting_field( $args ) {
        $options = get_option('slr_plugin_options');
        $value = $options[$args['id']] ?? ($args['default'] ?? '');
        $type = $args['type'] ?? 'text';

        if ($type === 'checkbox') {
             echo '<input type="checkbox" id="' . esc_attr($args['id']) . '" name="slr_plugin_options[' . esc_attr($args['id']) . ']" value="1" ' . checked($value, 1, false) . ' />';
        } else {
             echo '<input type="' . esc_attr($type) . '" id="' . esc_attr($args['id']) . '" name="slr_plugin_options[' . esc_attr($args['id']) . ']" value="' . esc_attr($value) . '" class="regular-text" />';
        }
       
        if (!empty($args['desc'])) {
            echo '<p class="description">' . esc_html($args['desc']) . '</p>';
        }
    }
}