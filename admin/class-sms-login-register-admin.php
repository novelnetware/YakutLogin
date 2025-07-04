<?php
/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://yakut.ir/
 * @since      1.0.0
 *
 * @package    Sms_Login_Register
 * @subpackage Sms_Login_Register/admin
 */

class Sms_Login_Register_Admin {

    private $plugin_name;
    private $version;
    private $gateway_manager;

    public function __construct( $plugin_name, $version) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
        if (class_exists('SLR_Gateway_Manager')) {
            $this->gateway_manager = new SLR_Gateway_Manager();
        } else {
            $this->gateway_manager = null;
        }
        add_action( 'wp_ajax_yakutlogin_save_settings', array( $this, 'ajax_save_settings' ) );
        add_action( 'wp_ajax_yakutlogin_get_gateway_fields', array( $this, 'ajax_get_gateway_fields' ) );
        add_action( 'wp_ajax_yakutlogin_cleanup_data', array( $this, 'ajax_cleanup_data' ) );
    }

/**
 * لیست کامل تمام فیلدهای checkbox در پنل تنظیمات
 */
private function get_all_checkbox_fields() {
    return [
        'email_otp_enabled',
        'google_login_enabled',
        'wc_checkout_otp_integration',
        // فیلدهای checkbox مربوط به درگاه‌های پیامک
        'kavenegar_use_lookup',
        'melipayamak_is_shared',
        'smsir_fast_mode',
        'payamresan_use_template',
        'ghasedaksms_use_pattern',
        // هر checkbox دیگری که در آینده اضافه می‌کنید
    ];
}

/**
 * هندلر AJAX برای ذخیره تنظیمات (نسخه نهایی و اصلاح شده)
 */
public function ajax_save_settings() {
    check_ajax_referer('yakutlogin_admin_nonce', 'nonce');

    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => 'دسترسی غیرمجاز']);
    }

    // ۱. لیست کامل و صحیح تمام فیلدهای ممکن
    $all_possible_options = [
        // تنظیمات عمومی و ایمیل
        'email_otp_enabled' => 'checkbox',
        'otp_email_subject' => 'text',
        'otp_email_body'    => 'editor',

        // درگاه پیامک
        'sms_provider'        => 'key',
        'sms_provider_backup' => 'key',

        // کاوه‌نگار
        'kavenegar_api_key'         => 'password',
        'kavenegar_sender_line'     => 'text',
        'kavenegar_use_lookup'      => 'checkbox',
        'kavenegar_lookup_template' => 'text',

        // ملی پیامک
        'melipayamak_username'  => 'text',
        'melipayamak_password'  => 'password',
        'melipayamak_from'      => 'text',
        'melipayamak_body_id'   => 'number',
        'melipayamak_is_shared' => 'checkbox',

        // کاوان اس‌ام‌اس
        'kavansms_api_key' => 'password',
        'kavansms_otp_id'  => 'text',
        
        // فراز اس‌ام‌اس
        'farazsms_username'          => 'text',
        'farazsms_password'          => 'password',
        'farazsms_from'              => 'text',
        'farazsms_pattern_code'      => 'text',
        'farazsms_otp_variable_name' => 'text',

        // SMS.ir
        'smsir_api_key'            => 'password',
        'smsir_template_id'        => 'number',
        'smsir_otp_parameter_name' => 'text',
        'smsir_fast_mode'          => 'checkbox',
        'smsir_line_number'        => 'text',

        // ورود با گوگل
        'google_login_enabled' => 'checkbox',
        'google_client_id'     => 'text',
        'google_client_secret' => 'password',

        // کپچا
        'captcha_type'              => 'key',
        'recaptcha_v2_site_key'   => 'text',
        'recaptcha_v2_secret_key' => 'password',
        'turnstile_site_key'      => 'text',
        'turnstile_secret_key'    => 'password',

        // ووکامرس
        'wc_checkout_otp_integration' => 'checkbox',
        
        // فیلدهای قدیمی‌تر برای سازگاری
        'ghasedaksms_api_key'     => 'password',
        'ghasedaksms_line_number' => 'text',
        'ghasedaksms_use_pattern' => 'checkbox',
        'payamresan_username'     => 'text',
        'payamresan_password'     => 'password',
        'payamresan_from'         => 'text',
        'payamresan_use_template' => 'checkbox',
        'sms_otp_template'        => 'text',
    ];


    // ۲. دریافت داده‌های ارسالی
    $submitted_data = [];
    if (isset($_POST['settings'])) {
        parse_str(wp_unslash($_POST['settings']), $submitted_data);
    } else {
        wp_send_json_error(['message' => 'فرمت داده‌ها نامعتبر است']);
    }

    // ۳. دریافت تنظیمات فعلی
    $current_options = get_option('slr_plugin_options', []);

    // ۴. پردازش تمام فیلدها بر اساس لیست کامل
    foreach ($all_possible_options as $key => $type) {
        if ($type === 'checkbox') {
            // برای چک‌باکس‌ها: اگر ارسال شده باشد 1، وگرنه 0 ذخیره شود
            $current_options[$key] = isset($submitted_data[$key]) ? 1 : 0;
        } elseif (isset($submitted_data[$key])) {
            // برای سایر فیلدها: فقط اگر مقداری برایشان ارسال شده باشد، پردازش شوند
            $value = $submitted_data[$key];
            switch ($type) {
                case 'editor':
            case 'textarea':
                $current_options[$key] = wp_kses_post($value);
                break;
                case 'password':
                case 'text':
                    $current_options[$key] = sanitize_text_field($value);
                    break;
                case 'number':
                    $current_options[$key] = intval($value);
                    break;
                case 'key':
                    $current_options[$key] = sanitize_key($value);
                    break;
                default:
                    $current_options[$key] = sanitize_text_field($value);
            }
        }
    }

    // ۵. ذخیره نهایی
    update_option('slr_plugin_options', $current_options);
    
    wp_send_json_success(['message' => 'تنظیمات با موفقیت ذخیره شد!']);
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


  

public function enqueue_styles( $hook ) {
    // فقط در صفحه تنظیمات افزونه این فایل‌ها را بارگذاری کن
    if ( 'toplevel_page_' . $this->plugin_name . '-settings' !== $hook ) {
        return;
    }
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

        $icon_svg_base64 = 'data:image/svg+xml;base64,PD94bWwgdmVyc2lvbj0iMS4wIiBlbmNvZGluZz0iVVRGLTgiPz4KPHN2ZyBpZD0iTE9HTyIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIiB4bWxuczp4bGluaz0iaHR0cDovL3d3dy53My5vcmcvMTk5OS94bGluayIgdmlld0JveD0iMCAwIDE3NjAuMiAxMDY5Ljk4Ij4KICA8ZGVmcz4KICAgIDxzdHlsZT4KICAgICAgLmNscy0xIHsKICAgICAgICBtYXNrOiB1cmwoI21hc2stNSk7CiAgICAgIH0KCiAgICAgIC5jbHMtMiB7CiAgICAgICAgZmlsbDogdXJsKCNsaW5lYXItZ3JhZGllbnQtMik7CiAgICAgIH0KCiAgICAgIC5jbHMtMiwgLmNscy0zIHsKICAgICAgICBtaXgtYmxlbmQtbW9kZTogbXVsdGlwbHk7CiAgICAgIH0KCiAgICAgIC5jbHMtNCB7CiAgICAgICAgbWFzazogdXJsKCNtYXNrLTEpOwogICAgICB9CgogICAgICAuY2xzLTUgewogICAgICAgIG1hc2s6IHVybCgjbWFzayk7CiAgICAgIH0KCiAgICAgIC5jbHMtNiB7CiAgICAgICAgbWFzazogdXJsKCNtYXNrLTIpOwogICAgICB9CgogICAgICAuY2xzLTcgewogICAgICAgIGZpbHRlcjogdXJsKCNsdW1pbm9zaXR5LW5vY2xpcC0zKTsKICAgICAgfQoKICAgICAgLmNscy04IHsKICAgICAgICBmaWxsOiB1cmwoI2xpbmVhci1ncmFkaWVudC0zKTsKICAgICAgfQoKICAgICAgLmNscy05IHsKICAgICAgICBmaWxsOiB1cmwoI2xpbmVhci1ncmFkaWVudC01KTsKICAgICAgfQoKICAgICAgLmNscy0zIHsKICAgICAgICBmaWxsOiB1cmwoI2xpbmVhci1ncmFkaWVudC00KTsKICAgICAgfQoKICAgICAgLmNscy0xMCB7CiAgICAgICAgZmlsbDogcmdiYSgyNTAsIDIxMSwgMTI5LCAuMik7CiAgICAgIH0KCiAgICAgIC5jbHMtMTEgewogICAgICAgIGZpbGw6IHJnYmEoNjAsIDIxOSwgODIsIC4yKTsKICAgICAgfQoKICAgICAgLmNscy0xMiB7CiAgICAgICAgbWFzazogdXJsKCNtYXNrLTQpOwogICAgICB9CgogICAgICAuY2xzLTEzIHsKICAgICAgICBmaWxsOiByZ2JhKDY1LCA2NCwgMTcsIC4xNSk7CiAgICAgIH0KCiAgICAgIC5jbHMtMTQgewogICAgICAgIGZpbGw6ICMyYjJiMDU7CiAgICAgIH0KCiAgICAgIC5jbHMtMTUgewogICAgICAgIGZpbGw6IHVybCgjbGluZWFyLWdyYWRpZW50KTsKICAgICAgfQoKICAgICAgLmNscy0xNiB7CiAgICAgICAgZmlsbDogcmdiYSgxMzAsIDIyOCwgOTgsIC4yKTsKICAgICAgfQoKICAgICAgLmNscy0xNyB7CiAgICAgICAgbWFzazogdXJsKCNtYXNrLTMpOwogICAgICB9CgogICAgICAuY2xzLTE4IHsKICAgICAgICBmaWx0ZXI6IHVybCgjbHVtaW5vc2l0eS1ub2NsaXApOwogICAgICB9CiAgICA8L3N0eWxlPgogICAgPGxpbmVhckdyYWRpZW50IGlkPSJsaW5lYXItZ3JhZGllbnQiIHgxPSI4ODAuMSIgeTE9Ii0zNTcuNjgiIHgyPSI4ODAuMSIgeTI9IjEzNTkuOTgiIGdyYWRpZW50VHJhbnNmb3JtPSJ0cmFuc2xhdGUoMCAxMjAzLjM3KSBzY2FsZSgxIC0xKSIgZ3JhZGllbnRVbml0cz0idXNlclNwYWNlT25Vc2UiPgogICAgICA8c3RvcCBvZmZzZXQ9IjAiIHN0b3AtY29sb3I9IiM1MjBjMTMiLz4KICAgICAgPHN0b3Agb2Zmc2V0PSIxIiBzdG9wLWNvbG9yPSIjM2FjNjFjIi8+CiAgICA8L2xpbmVhckdyYWRpZW50PgogICAgPGZpbHRlciBpZD0ibHVtaW5vc2l0eS1ub2NsaXAiIHg9Ijc0LjM1IiB5PSI0NTYuNDMiIHdpZHRoPSI0ODcuNiIgaGVpZ2h0PSIyNDYuMDUiIGNvbG9yLWludGVycG9sYXRpb24tZmlsdGVycz0ic1JHQiIgZmlsdGVyVW5pdHM9InVzZXJTcGFjZU9uVXNlIj4KICAgICAgPGZlRmxvb2QgZmxvb2QtY29sb3I9IiNmZmYiIHJlc3VsdD0iYmciLz4KICAgICAgPGZlQmxlbmQgaW49IlNvdXJjZUdyYXBoaWMiIGluMj0iYmciLz4KICAgIDwvZmlsdGVyPgogICAgPGZpbHRlciBpZD0ibHVtaW5vc2l0eS1ub2NsaXAtMiIgeD0iNzQuMzUiIHk9Ii04NzkyLjY5IiB3aWR0aD0iNDg3LjYiIGhlaWdodD0iMzI3NjYiIGNvbG9yLWludGVycG9sYXRpb24tZmlsdGVycz0ic1JHQiIgZmlsdGVyVW5pdHM9InVzZXJTcGFjZU9uVXNlIj4KICAgICAgPGZlRmxvb2QgZmxvb2QtY29sb3I9IiNmZmYiIHJlc3VsdD0iYmciLz4KICAgICAgPGZlQmxlbmQgaW49IlNvdXJjZUdyYXBoaWMiIGluMj0iYmciLz4KICAgIDwvZmlsdGVyPgogICAgPG1hc2sgaWQ9Im1hc2stMiIgeD0iNzQuMzUiIHk9Ii04NzkyLjY5IiB3aWR0aD0iNDg3LjYiIGhlaWdodD0iMzI3NjYiIG1hc2tVbml0cz0idXNlclNwYWNlT25Vc2UiLz4KICAgIDxsaW5lYXJHcmFkaWVudCBpZD0ibGluZWFyLWdyYWRpZW50LTIiIHgxPSIxMDcuMDciIHkxPSI4MzkuMzQiIHgyPSIzMzIuNjciIHkyPSI2MDkuMSIgZ3JhZGllbnRUcmFuc2Zvcm09InRyYW5zbGF0ZSgwIDEyMDMuMzcpIHNjYWxlKDEgLTEpIiBncmFkaWVudFVuaXRzPSJ1c2VyU3BhY2VPblVzZSI+CiAgICAgIDxzdG9wIG9mZnNldD0iMCIgc3RvcC1jb2xvcj0iI2ZmZiIvPgogICAgICA8c3RvcCBvZmZzZXQ9IjEiIHN0b3AtY29sb3I9IiMwMDAiLz4KICAgIDwvbGluZWFyR3JhZGllbnQ+CiAgICA8bWFzayBpZD0ibWFzay0xIiB4PSI3NC4zNSIgeT0iNDU2LjQzIiB3aWR0aD0iNDg3LjYiIGhlaWdodD0iMjQ2LjA1IiBtYXNrVW5pdHM9InVzZXJTcGFjZU9uVXNlIj4KICAgICAgPGcgY2xhc3M9ImNscy0xOCI+CiAgICAgICAgPGcgY2xhc3M9ImNscy02Ij4KICAgICAgICAgIDxyZWN0IGNsYXNzPSJjbHMtMiIgeD0iNzQuMzUiIHk9IjQ1Ni40MyIgd2lkdGg9IjQ4Ny42IiBoZWlnaHQ9IjI0Ni4wNSIvPgogICAgICAgIDwvZz4KICAgICAgPC9nPgogICAgPC9tYXNrPgogICAgPGxpbmVhckdyYWRpZW50IGlkPSJsaW5lYXItZ3JhZGllbnQtMyIgeDE9IjEwNy4wNyIgeTE9IjgzOS4zNCIgeDI9IjMzMi42NyIgeTI9IjYwOS4xIiBncmFkaWVudFRyYW5zZm9ybT0idHJhbnNsYXRlKDAgMTIwMy4zNykgc2NhbGUoMSAtMSkiIGdyYWRpZW50VW5pdHM9InVzZXJTcGFjZU9uVXNlIj4KICAgICAgPHN0b3Agb2Zmc2V0PSIwIiBzdG9wLWNvbG9yPSIjZmZmIi8+CiAgICAgIDxzdG9wIG9mZnNldD0iMSIgc3RvcC1jb2xvcj0iI2ZmZiIvPgogICAgPC9saW5lYXJHcmFkaWVudD4KICAgIDxtYXNrIGlkPSJtYXNrIiB4PSI3NC4zNSIgeT0iNDU2LjQzIiB3aWR0aD0iNDg3LjYiIGhlaWdodD0iMjQ2LjA1IiBtYXNrVW5pdHM9InVzZXJTcGFjZU9uVXNlIj4KICAgICAgPGcgaWQ9ImlkMCI+CiAgICAgICAgPGcgY2xhc3M9ImNscy00Ij4KICAgICAgICAgIDxyZWN0IGNsYXNzPSJjbHMtOCIgeD0iNzQuMzUiIHk9IjQ1Ni40MyIgd2lkdGg9IjQ4Ny42IiBoZWlnaHQ9IjI0Ni4wNSIvPgogICAgICAgIDwvZz4KICAgICAgPC9nPgogICAgPC9tYXNrPgogICAgPGZpbHRlciBpZD0ibHVtaW5vc2l0eS1ub2NsaXAtMyIgeD0iNjYuNjMiIHk9Ijk5Ljk4IiB3aWR0aD0iNDAwLjM0IiBoZWlnaHQ9IjE5MC4yNiIgY29sb3ItaW50ZXJwb2xhdGlvbi1maWx0ZXJzPSJzUkdCIiBmaWx0ZXJVbml0cz0idXNlclNwYWNlT25Vc2UiPgogICAgICA8ZmVGbG9vZCBmbG9vZC1jb2xvcj0iI2ZmZiIgcmVzdWx0PSJiZyIvPgogICAgICA8ZmVCbGVuZCBpbj0iU291cmNlR3JhcGhpYyIgaW4yPSJiZyIvPgogICAgPC9maWx0ZXI+CiAgICA8ZmlsdGVyIGlkPSJsdW1pbm9zaXR5LW5vY2xpcC00IiB4PSI2Ni42MyIgeT0iLTg3OTIuNjkiIHdpZHRoPSI0MDAuMzQiIGhlaWdodD0iMzI3NjYiIGNvbG9yLWludGVycG9sYXRpb24tZmlsdGVycz0ic1JHQiIgZmlsdGVyVW5pdHM9InVzZXJTcGFjZU9uVXNlIj4KICAgICAgPGZlRmxvb2QgZmxvb2QtY29sb3I9IiNmZmYiIHJlc3VsdD0iYmciLz4KICAgICAgPGZlQmxlbmQgaW49IlNvdXJjZUdyYXBoaWMiIGluMj0iYmciLz4KICAgIDwvZmlsdGVyPgogICAgPG1hc2sgaWQ9Im1hc2stNSIgeD0iNjYuNjMiIHk9Ii04NzkyLjY5IiB3aWR0aD0iNDAwLjM0IiBoZWlnaHQ9IjMyNzY2IiBtYXNrVW5pdHM9InVzZXJTcGFjZU9uVXNlIi8+CiAgICA8bGluZWFyR3JhZGllbnQgaWQ9ImxpbmVhci1ncmFkaWVudC00IiB4MT0iMTEyLjQ5IiB5MT0iNzkyLjI5IiB4Mj0iMzI1LjQ1IiB5Mj0iMTA5MC4zNSIgZ3JhZGllbnRUcmFuc2Zvcm09InRyYW5zbGF0ZSgwIDEyMDMuMzcpIHNjYWxlKDEgLTEpIiBncmFkaWVudFVuaXRzPSJ1c2VyU3BhY2VPblVzZSI+CiAgICAgIDxzdG9wIG9mZnNldD0iMCIgc3RvcC1jb2xvcj0iI2ZmZiIvPgogICAgICA8c3RvcCBvZmZzZXQ9IjEiIHN0b3AtY29sb3I9IiMwMDAiLz4KICAgIDwvbGluZWFyR3JhZGllbnQ+CiAgICA8bWFzayBpZD0ibWFzay00IiB4PSI2Ni42MyIgeT0iOTkuOTgiIHdpZHRoPSI0MDAuMzQiIGhlaWdodD0iMTkwLjI2IiBtYXNrVW5pdHM9InVzZXJTcGFjZU9uVXNlIj4KICAgICAgPGcgY2xhc3M9ImNscy03Ij4KICAgICAgICA8ZyBjbGFzcz0iY2xzLTEiPgogICAgICAgICAgPHJlY3QgY2xhc3M9ImNscy0zIiB4PSI2Ni42MyIgeT0iOTkuOTgiIHdpZHRoPSI0MDAuMzQiIGhlaWdodD0iMTkwLjI2Ii8+CiAgICAgICAgPC9nPgogICAgICA8L2c+CiAgICA8L21hc2s+CiAgICA8bGluZWFyR3JhZGllbnQgaWQ9ImxpbmVhci1ncmFkaWVudC01IiB4MT0iMTEyLjQ5IiB5MT0iNzkyLjI5IiB4Mj0iMzI1LjQ1IiB5Mj0iMTA5MC4zNSIgZ3JhZGllbnRUcmFuc2Zvcm09InRyYW5zbGF0ZSgwIDEyMDMuMzcpIHNjYWxlKDEgLTEpIiBncmFkaWVudFVuaXRzPSJ1c2VyU3BhY2VPblVzZSI+CiAgICAgIDxzdG9wIG9mZnNldD0iMCIgc3RvcC1jb2xvcj0iI2ZmZiIvPgogICAgICA8c3RvcCBvZmZzZXQ9IjEiIHN0b3AtY29sb3I9IiNmZmYiLz4KICAgIDwvbGluZWFyR3JhZGllbnQ+CiAgICA8bWFzayBpZD0ibWFzay0zIiB4PSI2Ni42MyIgeT0iOTkuOTgiIHdpZHRoPSI0MDAuMzQiIGhlaWdodD0iMTkwLjI2IiBtYXNrVW5pdHM9InVzZXJTcGFjZU9uVXNlIj4KICAgICAgPGcgaWQ9ImlkMiI+CiAgICAgICAgPGcgY2xhc3M9ImNscy0xMiI+CiAgICAgICAgICA8cmVjdCBjbGFzcz0iY2xzLTkiIHg9IjY2LjYzIiB5PSI5OS45OCIgd2lkdGg9IjQwMC4zNCIgaGVpZ2h0PSIxOTAuMjYiLz4KICAgICAgICA8L2c+CiAgICAgIDwvZz4KICAgIDwvbWFzaz4KICA8L2RlZnM+CiAgPHBvbHlnb24gY2xhc3M9ImNscy0xNSIgcG9pbnRzPSIyNzkuNzIgMCAwIDM4Mi4xIDY4Ny44OCAxMDY5Ljk4IDExODIuMDIgNTc1Ljg0IDk0MC40MyA1NzUuODQgNjg3Ljg4IDgyOC4zOSAzMTguMjQgNDU4Ljc3IDEyOTkuMTEgNDU4Ljc3IDE0NjkuOTQgMjg3LjkyIDI4MC4xOSAyODcuOTIgMzY1LjkxIDE3MC44MyAxNTg2LjI5IDE3MC44MyAxNzYwLjIgMCAyNzkuNzIgMCIvPgogIDxwYXRoIGNsYXNzPSJjbHMtMTMiIGQ9Ik02NDcuMjQsMjg3LjkyaDgyMi43bC0xNzAuODMsMTcwLjg1SDQ4NC4zNGM0Ny4wMi02My41LDEwMS43OS0xMjAuOTMsMTYyLjktMTcwLjg1Wk00MTkuMTQsNTU5LjY1bDI2OC43NCwyNjguNzQsMjUyLjU1LTI1Mi41NWgyNDEuNTlsLTQ5NC4xNCw0OTQuMTQtMzQyLjM4LTM0Mi4zOGMxOS4xOC01OC43LDQzLjkyLTExNC45LDczLjY0LTE2Ny45NWgwWk0xNjEwLjQ5LDE0Ny4wN2wtMjQuMiwyMy43NmgtNzU2LjY1YzEyNS43NS02MS42NywyNjcuMTMtOTYuMzUsNDE2LjY0LTk2LjM1LDEyOS4wOCwwLDI1Mi4wOCwyNS44NiwzNjQuMjEsNzIuNTlaIi8+CiAgPHBhdGggY2xhc3M9ImNscy0xMSIgZD0iTTI4MC4xOSwyODcuOTJoMjg0LjQxYzI0LjMyLDU0LjQ0LDQzLjY3LDExMS41Nyw1Ny40NywxNzAuODVoLTMwMy44NGwzMjIuMzcsMzIyLjM1Yy03LjY4LDY4LjMyLTIyLjU1LDEzNC40Ni00My45OSwxOTcuNkwwLDM4Mi4xLDI3OS43MiwwaDg1LjJjNTEuOTgsNTEuMzUsOTguMDQsMTA4LjY3LDEzNy4xMiwxNzAuODNoLTEzNi4xM2wtODUuNzIsMTE3LjA5aDBaIi8+CiAgPHBhdGggY2xhc3M9ImNscy0xMSIgZD0iTTgxNy42NywyODcuOTJoNjUyLjI3bC0xNzAuODMsMTcwLjg1aC0yMzguNTFjLTg5Ljg5LTQ0LjA4LTE3MS43OS0xMDEuOTMtMjQyLjkzLTE3MC44NVpNNjE0LjM1LDBoMTE0NS44NWwtMTczLjkxLDE3MC44M2gtODcxLjE5Yy0zOS4yNS01Mi45Ni03My4xLTExMC4xOS0xMDAuNzQtMTcwLjgzWiIvPgogIDxwb2x5Z29uIGNsYXNzPSJjbHMtMTYiIHBvaW50cz0iMTAuMjMgMzkyLjMyIDAgMzgyLjEgMjc5LjcyIDAgMTc2MC4yIDAgMTczNS41NyAyNC4yIDI3OS43MiAyNC4yIDg2LjY1IDI4Ny45MiAxNDY5Ljk0IDI4Ny45MiAxNDQ1LjczIDMxMi4xMiA2OC45NSAzMTIuMTIgMTAuMjMgMzkyLjMyIi8+CiAgPHBvbHlnb24gY2xhc3M9ImNscy0xMCIgcG9pbnRzPSI5NDAuNDMgNTc1Ljg0IDExODIuMDIgNTc1Ljg0IDExNTcuODIgNjAwLjA0IDk0MC40MyA2MDAuMDQgNjk5Ljk4IDg0MC40OSA2ODcuODggODI4LjM5IDk0MC40MyA1NzUuODQiLz4KICA8ZyBjbGFzcz0iY2xzLTUiPgogICAgPHBvbHlnb24gY2xhc3M9ImNscy0xNCIgcG9pbnRzPSIzMTguMjQgNDU4Ljc1IDU1OS42MyA3MDAuMTYgMzE4LjA2IDcwMC4xNiA3Ni42NyA0NTguNzUgMzE4LjI0IDQ1OC43NSIvPgogIDwvZz4KICA8ZyBjbGFzcz0iY2xzLTE3Ij4KICAgIDxwb2x5Z29uIGNsYXNzPSJjbHMtMTQiIHBvaW50cz0iNjguOTUgMjg3LjkyIDIwNC44MSAxMDIuMzEgNDE2LjA2IDEwMi4zMSA0NjQuNjQgMTcwLjgzIDM2NS42OSAxNzEuMTQgMjgwLjE5IDI4Ny45MiA2OC45NSAyODcuOTIiLz4KICA8L2c+Cjwvc3ZnPg==';
        add_menu_page(
            __( 'یاکوت لاگین', 'yakutlogin' ),
            __( 'یاکوت لاگین', 'yakutlogin' ),
            'manage_options',
            $this->plugin_name . '-settings',
            array( $this, 'display_plugin_setup_page' ),
            $icon_svg_base64,
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
        case 'password': // handle password fields
            echo '<input type="' . esc_attr($type) . '" class="setting-input regular-text" name="' . esc_attr($id) . '" value="' . esc_attr($value) . '" placeholder="' . esc_attr($placeholder) . '">';
            break;
        
        case 'textarea':
            echo '<textarea class="setting-textarea large-text" rows="5" name="' . esc_attr($id) . '">' . esc_textarea($value) . '</textarea>';
            break;

        // --- NEW CASE FOR WP EDITOR ---
        case 'editor':
            $editor_settings = [
                'textarea_name' => esc_attr($id),
                'textarea_rows' => 12, // افزایش ارتفاع پیش‌فرض
                'media_buttons' => false,
                'tinymce'       => [
                    'toolbar1' => 'bold,italic,underline,link,unlink,bullist,numlist,undo,redo,alignleft,aligncenter,alignright',
                    'toolbar2' => '',
                ],
                'quicktags'     => true, // فعال کردن تب Text
            ];
            wp_editor(wp_kses_post($value), esc_attr($id) . '_editor', $editor_settings);
            break;
        // --- END NEW CASE ---

        case 'select_gateway':
        case 'select_gateway_backup':
            $gateways = $this->gateway_manager->get_available_gateways();
            $select_id = ($type === 'select_gateway') ? 'primary-sms-provider-select' : 'backup-sms-provider-select';

            echo '<select name="' . esc_attr($id) . '" class="setting-select" id="' . esc_attr($select_id) . '">';
            echo '<option value="">-- غیرفعال --</option>';
            foreach ($gateways as $gateway_id => $gateway) {
                echo '<option value="' . esc_attr($gateway_id) . '" ' . selected($gateway_id, $value, false) . '>' . esc_html($gateway->get_name()) . '</option>';
            }
            echo '</select>';
            break;
            
        case 'select_captcha': // New case for captcha dropdown
            $captcha_types = [
                'none' => __('غیرفعال', 'yakutlogin'),
                'recaptcha_v2' => __('Google reCAPTCHA v2', 'yakutlogin'),
                'turnstile' => __('Cloudflare Turnstile', 'yakutlogin')
            ];
            echo '<select name="' . esc_attr($id) . '" class="setting-select" id="captcha-type-select">';
            foreach ($captcha_types as $key => $label) {
                echo '<option value="' . esc_attr($key) . '" ' . selected($key, $value, false) . '>' . esc_html($label) . '</option>';
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

    /**
 * پاک‌سازی تنظیمات (نسخه نهایی)
 */
public function sanitize_settings($input) {
    $sanitized = [];

    if (!is_array($input)) {
        return $sanitized;
    }

    foreach ($input as $key => $value) {
        // مدیریت انواع فیلدها
        if (strpos($key, '_body_id') !== false || strpos($key, '_template_id') !== false) {
            $sanitized[$key] = absint($value);
        } elseif (strpos($key, '_api_key') !== false || strpos($key, '_password') !== false) {
            $sanitized[$key] = sanitize_text_field($value);
        } elseif (strpos($key, '_template') !== false || strpos($key, '_pattern') !== false) {
            $sanitized[$key] = sanitize_textarea_field($value);
        } else {
            $sanitized[$key] = sanitize_text_field($value);
        }
    }

    return $sanitized;
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

    public function ajax_cleanup_data() {
    check_ajax_referer('yakutlogin_admin_nonce', 'nonce');
    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => 'دسترسی غیرمجاز']);
    }

    // ۱. حذف جداول
    global $wpdb;

    // ۲. حذف گزینه‌ها
    delete_option('slr_plugin_options');

    // ۳. حذف تمام ترنزینت‌ها
    $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '\_transient\_slr\_%' OR option_name LIKE '\_transient\_timeout\_slr\_%'");

    wp_send_json_success(['message' => 'تمام اطلاعات افزونه با موفقیت پاکسازی شد. صفحه به زودی رفرش می‌شود.']);
}
}


