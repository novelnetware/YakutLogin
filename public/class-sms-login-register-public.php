<?php
/**
 * The public-facing functionality of the plugin.
 * This class acts as an orchestrator for the public-facing side of the plugin,
 * loading dependencies and initializing services.
 *
 * @package     Sms_Login_Register
 * @subpackage  Sms_Login_Register/public
 * @author      Yakut Co <info@yakut.ir>
 */
if ( ! defined( 'ABSPATH' ) ) exit;

class Sms_Login_Register_Public {

    private $plugin_name;
    private $version;
    private $theme_manager;
    private $asset_manager;

    public function __construct($plugin_name, $version, $theme_manager) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
        $this->theme_manager = $theme_manager;

        $this->load_dependencies();
        $this->init_services();
    }

    /**
     * Load the required dependency files for the public area.
     */
    private function load_dependencies() {
        $core_path = plugin_dir_path(__FILE__) . 'core/';
        require_once $core_path . 'class-slr-asset-manager.php';
        require_once $core_path . 'class-slr-user-handler.php';
        require_once $core_path . 'class-slr-oauth-handler.php';
        require_once $core_path . 'class-slr-ajax-handler-public.php';
    }

    /**
     * Initialize the services (classes) that power the public-facing features.
     */
    private function init_services() {
        // Asset manager is responsible for all scripts and styles
        $this->asset_manager = new SLR_Asset_Manager($this->plugin_name, $this->version, $this->theme_manager);

        // OAuth handler manages social login flows (Google, Discord, etc.)
        $oauth_handler = new SLR_OAuth_Handler();
        $oauth_handler->init_hooks();

        // Public AJAX handler manages OTP, Telegram, and Bale flows
        new SLR_Ajax_Handler_Public();

        // Register the main shortcode
        add_shortcode('slr_otp_form', [$this, 'render_slr_otp_form_shortcode']);
        
        // Hook for handling wp_mail failures
        add_action('wp_mail_failed', [$this, 'handle_wp_mail_failed']);
    }

    /**
     * Generates the HTML for the OTP login/registration form.
     * This function acts as an orchestrator for the dynamic theme engine.
     *
     * @param array $args Arguments to customize the form.
     * @return string HTML of the form.
     */
    public function get_otp_form_html($args = []) {
        // 1. Enqueue the main plugin's scripts via the Asset Manager
        $this->asset_manager->maybe_enqueue_scripts();

        // 2. Define default arguments for the form
        $default_args = [
            'form_id'      => 'slr-otp-form-' . wp_rand(100, 999),
            'show_labels'  => true,
            'redirect_to'  => '',
            'theme'        => 'default',
            'layout'       => 'default',
            'button_texts' => [
                'send_otp' => __('ارسال کد تایید', 'yakutlogin'),
                'submit'   => __('ورود / عضویت', 'yakutlogin'),
                'google'   => __('ورود با گوگل', 'yakutlogin'),
            ],
        ];

        // 3. Merge user-provided args with defaults
        $args = wp_parse_args($args, $default_args);
        $args['button_texts'] = wp_parse_args($args['button_texts'], $default_args['button_texts']);
        
        // 4. Get the selected theme object from the Theme Manager
        $theme = $this->theme_manager->get_theme($args['theme']);
        if (!$theme) {
            return '<p style="color: red;">خطا: پوسته انتخاب شده (' . esc_html($args['theme']) . ') یافت نشد.</p>';
        }

        // 5. Enqueue the specific assets for this theme via the Asset Manager
        $this->asset_manager->enqueue_theme_assets($args['theme']);

        // 6. Prepare common data to pass to the theme's render method
        $options = get_option('slr_plugin_options', []);
        
        // Social & Integration Logins
        $args['google_login_enabled']   = !empty($options['google_login_enabled']) && !empty($options['google_client_id']);
        $args['telegram_login_enabled'] = !empty($options['telegram_login_enabled']) && !empty($options['telegram_bot_token']);
        $args['bale_login_enabled']     = !empty($options['bale_login_enabled']);
        $args['discord_login_enabled']  = !empty($options['discord_login_enabled']);
        $args['linkedin_login_enabled'] = !empty($options['linkedin_login_enabled']);
        $args['github_login_enabled']   = !empty($options['github_login_enabled']);
        
        // Bale mode
        $args['bale_login_mode'] = $options['bale_login_mode'] ?? 'smart_only';

        // Google Login URL
        if ($args['google_login_enabled']) {
            $args['google_login_url'] = wp_nonce_url(add_query_arg('slr_action', 'google_login_init', home_url('/')), 'slr_google_login_init_nonce', 'slr_google_nonce');
        }
        
        // Captcha data
        $args['captcha_type'] = $options['captcha_type'] ?? 'none';
        $args['captcha_site_key'] = '';
        if ($args['captcha_type'] === 'recaptcha_v2' && !empty($options['recaptcha_v2_site_key'])) {
            $args['captcha_site_key'] = $options['recaptcha_v2_site_key'];
        } elseif ($args['captcha_type'] === 'turnstile' && !empty($options['turnstile_site_key'])) {
            $args['captcha_site_key'] = $options['turnstile_site_key'];
        }

        // 7. Start output buffering to build the final HTML
        ob_start();
        ?>
        <div id="<?php echo esc_attr($args['form_id']); ?>" class="slr-otp-form-container slr-theme-<?php echo esc_attr($args['theme']); ?> slr-layout-<?php echo esc_attr($args['layout']); ?>">
            <form class="slr-otp-form" method="post" onsubmit="return false;">
                <?php echo $theme->get_html($args); ?>
                <div class="slr-message-area"></div>
                <?php wp_nonce_field('slr_process_form_nonce', 'slr_process_form_nonce_field'); ?>
                <input type="hidden" name="slr_redirect_to" value="<?php echo esc_url($args['redirect_to']); ?>" />
            </form>
        </div>
        <?php
        
        return ob_get_clean();
    }

    /**
     * Renders the OTP form via shortcode.
     * @param array $atts Shortcode attributes.
     * @return string HTML output of the form.
     */
    public function render_slr_otp_form_shortcode($atts) {
        $atts = shortcode_atts([
            'show_labels'   => 'true',
            'redirect_to'   => '',
            'theme'         => 'default',
            'layout'        => 'default',
            'text_send_otp' => '',
            'text_submit'   => '',
            'text_google'   => '',
        ], $atts, 'slr_otp_form');

        $button_texts = [];
        if (!empty($atts['text_send_otp'])) $button_texts['send_otp'] = sanitize_text_field($atts['text_send_otp']);
        if (!empty($atts['text_submit'])) $button_texts['submit'] = sanitize_text_field($atts['text_submit']);
        if (!empty($atts['text_google'])) $button_texts['google'] = sanitize_text_field($atts['text_google']);

        return $this->get_otp_form_html([
            'show_labels'  => filter_var($atts['show_labels'], FILTER_VALIDATE_BOOLEAN),
            'redirect_to'  => !empty($atts['redirect_to']) ? esc_url($atts['redirect_to']) : '',
            'theme'        => sanitize_html_class($atts['theme']),
            'layout'       => sanitize_html_class($atts['layout']),
            'button_texts' => $button_texts,
        ]);
    }
    
    /**
     * Sends an OTP email to the user with proper headers.
     * This method can stay here as a utility.
     */
    public function send_otp_email($email_address, $otp) {
        $options = get_option('slr_plugin_options');
        if (empty($options['email_otp_enabled'])) {
            return false;
        }

        $subject_template = $options['otp_email_subject'] ?? 'کد تایید شما';
        $body_template    = $options['otp_email_body'] ?? 'کد تایید شما: {otp_code}';
        
        $placeholders = [
            '{otp_code}'   => $otp,
            '{site_title}' => get_bloginfo('name'),
            '{site_url}'   => home_url(),
        ];
        
        $subject = str_replace(array_keys($placeholders), array_values($placeholders), $subject_template);
        $body    = nl2br(str_replace(array_keys($placeholders), array_values($placeholders), $body_template));

        $headers = [
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . get_bloginfo('name') . ' <noreply@' . preg_replace('#^www\.#', '', strtolower($_SERVER['SERVER_NAME'])) . '>',
        ];

        return wp_mail($email_address, $subject, $body, $headers);
    }
    
    /**
     * Logs critical wp_mail failures.
     */
    public function handle_wp_mail_failed($wp_error) {
        slr_log('--- WP_MAIL CRITICAL ERROR ---');
        slr_log($wp_error);
        slr_log('--- END WP_MAIL ERROR ---');
    }
}