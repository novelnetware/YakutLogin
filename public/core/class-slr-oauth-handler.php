<?php
/**
 * Handles all Social Login (OAuth2) flows for providers like Google, Discord, etc.
 *
 * @package     Sms_Login_Register
 * @subpackage  Sms_Login_Register/public/core
 * @since       1.4.0
 */
if ( ! defined( 'ABSPATH' ) ) exit;

class SLR_OAuth_Handler {

    /**
     * The main plugin options.
     * @var array
     */
    private $options;

    public function __construct() {
        $this->options = get_option('slr_plugin_options', []);
    }

    /**
     * Register all hooks related to OAuth flows.
     */
    public function init_hooks() {
        // Init hooks are triggered on 'init' to catch the request before headers are sent.
        add_action('init', [ $this, 'init_google_login' ]);
        add_action('init', [ $this, 'handle_google_callback' ]);
        add_action('init', [ $this, 'init_discord_login' ]);
        add_action('init', [ $this, 'handle_discord_callback' ]);
        add_action('init', [ $this, 'init_linkedin_login' ]);
        add_action('init', [ $this, 'handle_linkedin_callback' ]);
        add_action('init', [ $this, 'init_github_login' ]);
        add_action('init', [ $this, 'handle_github_callback' ]);
    }

     //======================================================================
    // Google Login
    //======================================================================

    public function init_google_login() {
        if (!isset($_GET['slr_action']) || $_GET['slr_action'] !== 'google_login_init') return;
        
        if (!isset($_GET['slr_google_nonce']) || !wp_verify_nonce($_GET['slr_google_nonce'], 'slr_google_login_init_nonce')) {
            wp_die('Security check failed for Google login initiation.');
        }
        
        if (empty($this->options['google_login_enabled']) || empty($this->options['google_client_id'])) {
            wp_redirect(home_url());
            exit;
        }

        $auth_url = 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query([
            'client_id'     => $this->options['google_client_id'],
            'redirect_uri'  => add_query_arg('slr_google_auth_callback', '1', home_url('/')),
            'response_type' => 'code',
            'scope'         => 'openid email profile',
            'state'         => wp_create_nonce('slr_google_oauth_state')
        ]);
        
        wp_redirect($auth_url);
        exit;
    }

    public function handle_google_callback() {
        if (!isset($_GET['slr_google_auth_callback'])) return;

        if (!isset($_GET['state']) || !wp_verify_nonce($_GET['state'], 'slr_google_oauth_state')) {
            wp_redirect(home_url('/?slr_login_error=google_state_mismatch'));
            exit;
        }
        
        if (isset($_GET['error'])) {
            wp_redirect(home_url('/?slr_login_error=google_access_denied&error_desc=' . urlencode($_GET['error'])));
            exit;
        }

        if (empty($_GET['code'])) {
            wp_redirect(home_url('/?slr_login_error=google_no_code'));
            exit;
        }

        $code = sanitize_text_field($_GET['code']);
        $response = wp_remote_post('https://oauth2.googleapis.com/token', [
            'body' => [
                'code'          => $code,
                'client_id'     => $this->options['google_client_id'],
                'client_secret' => $this->options['google_client_secret'],
                'redirect_uri'  => add_query_arg('slr_google_auth_callback', '1', home_url('/')),
                'grant_type'    => 'authorization_code'
            ],
            'timeout' => 20
        ]);

        if (is_wp_error($response)) {
            wp_redirect(home_url('/?slr_login_error=google_token_wp_error'));
            exit;
        }
        
        $token_data = json_decode(wp_remote_retrieve_body($response), true);
        if (empty($token_data['access_token'])) {
            wp_redirect(home_url('/?slr_login_error=google_token_api_error'));
            exit;
        }

        $userinfo_response = wp_remote_get('https://www.googleapis.com/oauth2/v3/userinfo', [
            'headers' => ['Authorization' => 'Bearer ' . $token_data['access_token']],
            'timeout' => 20
        ]);

        if (is_wp_error($userinfo_response)) {
            wp_redirect(home_url('/?slr_login_error=google_userinfo_wp_error'));
            exit;
        }
        
        $user_info = json_decode(wp_remote_retrieve_body($userinfo_response), true);
        if (empty($user_info['email'])) {
            wp_redirect(home_url('/?slr_login_error=google_userinfo_api_error'));
            exit;
        }

        $this->login_or_register_oauth_user(
            sanitize_email($user_info['email']),
            'google',
            [
                'first_name' => $user_info['given_name'] ?? '',
                'last_name'  => $user_info['family_name'] ?? ''
            ]
        );
    }


    //======================================================================
    // Discord Login
    //======================================================================

    public function init_discord_login() {
        if (!isset($_GET['slr_action']) || $_GET['slr_action'] !== 'discord_login_init') return;
        
        if (!isset($_GET['slr_discord_nonce']) || !wp_verify_nonce($_GET['slr_discord_nonce'], 'slr_discord_login_init_nonce')) {
            wp_die('Security check failed.');
        }
        
        if (empty($this->options['discord_login_enabled']) || empty($this->options['discord_client_id'])) {
            wp_redirect(home_url());
            exit;
        }

        $auth_url = 'https://discord.com/api/oauth2/authorize?' . http_build_query([
            'client_id'     => $this->options['discord_client_id'],
            'redirect_uri'  => add_query_arg('slr_discord_auth_callback', '1', home_url('/')),
            'response_type' => 'code',
            'scope'         => 'identify email',
            'state'         => wp_create_nonce('slr_discord_oauth_state'),
        ]);

        wp_redirect($auth_url);
        exit;
    }

    public function handle_discord_callback() {
        if (!isset($_GET['slr_discord_auth_callback'])) return;

        if (empty($_GET['state']) || !wp_verify_nonce($_GET['state'], 'slr_discord_oauth_state')) {
            wp_redirect(home_url('/?slr_login_error=discord_state_mismatch'));
            exit;
        }
        
        if (isset($_GET['error']) || empty($_GET['code'])) {
            wp_redirect(home_url('/?slr_login_error=discord_access_denied'));
            exit;
        }
        
        $token_response = wp_remote_post('https://discord.com/api/v10/oauth2/token', [
            'headers' => ['Content-Type' => 'application/x-www-form-urlencoded'],
            'body'    => [
                'client_id'     => $this->options['discord_client_id'],
                'client_secret' => $this->options['discord_client_secret'],
                'grant_type'    => 'authorization_code',
                'code'          => sanitize_text_field($_GET['code']),
                'redirect_uri'  => add_query_arg('slr_discord_auth_callback', '1', home_url('/')),
                'scope'         => 'identify email',
            ]
        ]);
        
        $token_data = json_decode(wp_remote_retrieve_body($token_response), true);
        if (is_wp_error($token_response) || empty($token_data['access_token'])) {
            wp_redirect(home_url('/?slr_login_error=discord_token_error'));
            exit;
        }
        
        $user_response = wp_remote_get('https://discord.com/api/v10/users/@me', [
            'headers' => ['Authorization' => 'Bearer ' . $token_data['access_token']]
        ]);

        $user_info = json_decode(wp_remote_retrieve_body($user_response), true);
        if (empty($user_info['email'])) {
            wp_redirect(home_url('/?slr_login_error=discord_no_email'));
            exit;
        }

        $this->login_or_register_oauth_user(
            sanitize_email($user_info['email']),
            'discord',
            ['first_name' => $user_info['global_name'] ?? $user_info['username'] ?? '']
        );
    }

    public function init_linkedin_login() {
        if (!isset($_GET['slr_action']) || $_GET['slr_action'] !== 'linkedin_login_init') return;

        if (!isset($_GET['slr_linkedin_nonce']) || !wp_verify_nonce($_GET['slr_linkedin_nonce'], 'slr_linkedin_login_init_nonce')) {
            wp_die('Security check failed.');
        }
        
        $options = get_option('slr_plugin_options');
        if (empty($options['linkedin_login_enabled']) || empty($options['linkedin_client_id'])) {
            wp_redirect(home_url());
            exit;
        }

        $state = wp_create_nonce('slr_linkedin_oauth_state');
        set_transient('slr_linkedin_state_' . $state, 'valid', 5 * MINUTE_IN_SECONDS);

        $auth_url = 'https://www.linkedin.com/oauth/v2/authorization?' . http_build_query([
            'response_type' => 'code',
            'client_id'     => $options['linkedin_client_id'],
            'redirect_uri'  => add_query_arg('slr_linkedin_auth_callback', '1', home_url('/')),
            'state'         => $state,
            'scope'         => 'r_liteprofile r_emailaddress', // Scopes to get basic profile and email
        ]);

        wp_redirect($auth_url);
        exit;
    }

    public function handle_linkedin_callback() {
        if (!isset($_GET['slr_linkedin_auth_callback'])) return;

        $options = get_option('slr_plugin_options');
        if (empty($options['linkedin_login_enabled'])) return;
        
        if (empty($_GET['state']) || !get_transient('slr_linkedin_state_' . $_GET['state'])) {
            wp_redirect(home_url('/?slr_login_error=linkedin_state_mismatch'));
            exit;
        }
        delete_transient('slr_linkedin_state_' . $_GET['state']);

        if (empty($_GET['code'])) {
            wp_redirect(home_url('/?slr_login_error=linkedin_no_code'));
            exit;
        }

        // Exchange code for access token
        $token_response = wp_remote_post('https://www.linkedin.com/oauth/v2/accessToken', [
            'body' => [
                'grant_type'    => 'authorization_code',
                'code'          => sanitize_text_field($_GET['code']),
                'redirect_uri'  => add_query_arg('slr_linkedin_auth_callback', '1', home_url('/')),
                'client_id'     => $options['linkedin_client_id'],
                'client_secret' => $options['linkedin_client_secret'],
            ]
        ]);
        
        $token_data = json_decode(wp_remote_retrieve_body($token_response), true);
        if (empty($token_data['access_token'])) {
            wp_redirect(home_url('/?slr_login_error=linkedin_token_error'));
            exit;
        }

        // Get user's primary email
        $email_response = wp_remote_get('https://api.linkedin.com/v2/emailAddress?q=members&projection=(elements*(handle~))', [
            'headers' => ['Authorization' => 'Bearer ' . $token_data['access_token']]
        ]);
        $email_data = json_decode(wp_remote_retrieve_body($email_response), true);
        $email = $email_data['elements'][0]['handle~']['emailAddress'] ?? null;

        if (empty($email)) {
            wp_redirect(home_url('/?slr_login_error=linkedin_no_email'));
            exit;
        }

        // Find or create the WordPress user
        $user = $this->find_or_create_user($email, 'email');
        if (is_wp_error($user)) {
            wp_redirect(home_url('/?slr_login_error=user_creation_failed'));
            exit;
        }
        
        // Log the user in and redirect
        wp_set_current_user($user->ID, $user->user_login);
        wp_set_auth_cookie($user->ID, true);
        do_action('wp_login', $user->user_login, $user);

        wp_redirect(apply_filters('slr_social_login_redirect_url', home_url('/'), $user, 'linkedin'));
        exit;
    }

    public function init_github_login() {
        if (!isset($_GET['slr_action']) || $_GET['slr_action'] !== 'github_login_init') return;
        if (!isset($_GET['slr_github_nonce']) || !wp_verify_nonce($_GET['slr_github_nonce'], 'slr_github_login_init_nonce')) {
            wp_die('Security check failed.');
        }
        
        $options = get_option('slr_plugin_options');
        if (empty($options['github_login_enabled']) || empty($options['github_client_id'])) {
            wp_redirect(home_url());
            exit;
        }

        $state = wp_create_nonce('slr_github_oauth_state');
        set_transient('slr_github_state_' . $state, 'valid', 5 * MINUTE_IN_SECONDS);

        $auth_url = 'https://github.com/login/oauth/authorize?' . http_build_query([
            'client_id'    => $options['github_client_id'],
            'redirect_uri' => add_query_arg('slr_github_auth_callback', '1', home_url('/')),
            'scope'        => 'read:user user:email',
            'state'        => $state,
        ]);

        wp_redirect($auth_url);
        exit;
    }

    public function handle_github_callback() {
        if (!isset($_GET['slr_github_auth_callback'])) return;

        $options = get_option('slr_plugin_options');
        if (empty($options['github_login_enabled'])) return;

        if (empty($_GET['state']) || !get_transient('slr_github_state_' . $_GET['state'])) {
            wp_redirect(home_url('/?slr_login_error=github_state_mismatch'));
            exit;
        }
        delete_transient('slr_github_state_' . $_GET['state']);

        if (empty($_GET['code'])) {
            wp_redirect(home_url('/?slr_login_error=github_no_code'));
            exit;
        }

        // Exchange code for access token
        $token_response = wp_remote_post('https://github.com/login/oauth/access_token', [
            'headers' => ['Accept' => 'application/json'],
            'body'    => [
                'client_id'     => $options['github_client_id'],
                'client_secret' => $options['github_client_secret'],
                'code'          => sanitize_text_field($_GET['code']),
            ]
        ]);
        
        $token_data = json_decode(wp_remote_retrieve_body($token_response), true);
        if (empty($token_data['access_token'])) {
            wp_redirect(home_url('/?slr_login_error=github_token_error'));
            exit;
        }

        // Get user's primary, verified email
        $email_response = wp_remote_get('https://api.github.com/user/emails', [
            'headers' => ['Authorization' => 'Bearer ' . $token_data['access_token']]
        ]);
        $emails = json_decode(wp_remote_retrieve_body($email_response), true);
        $primary_email = null;
        if (is_array($emails)) {
            foreach ($emails as $email_item) {
                if ($email_item['primary'] && $email_item['verified']) {
                    $primary_email = $email_item['email'];
                    break;
                }
            }
        }

        if (!$primary_email) {
            wp_redirect(home_url('/?slr_login_error=github_no_verified_email'));
            exit;
        }

        // Find or create the WordPress user
        $user = $this->find_or_create_user($primary_email, 'email');
        if (is_wp_error($user)) {
            wp_redirect(home_url('/?slr_login_error=user_creation_failed'));
            exit;
        }

        // Log the user in and redirect
        wp_set_current_user($user->ID, $user->user_login);
        wp_set_auth_cookie($user->ID, true);
        do_action('wp_login', $user->user_login, $user);

        wp_redirect(apply_filters('slr_social_login_redirect_url', home_url('/'), $user, 'github'));
        exit;
    }


/**
     * **THE CORRECTED HELPER METHOD**
     * Generic helper to log in or register a user from an OAuth provider.
     * @param string $email The user's email from the provider.
     * @param string $provider The name of the provider (e.g., 'google').
     */
    private function login_or_register_oauth_user(string $email, string $provider) {
        // *** This is the key improvement: using the dedicated User Handler ***
        $user = SLR_User_Handler::find_or_create_user($email, 'email');

        if (is_wp_error($user)) {
            wp_redirect(home_url('/?slr_login_error=' . $provider . '_registration_failed&reason=' . urlencode($user->get_error_message())));
            exit;
        }

        wp_clear_auth_cookie();
        wp_set_current_user($user->ID);
        wp_set_auth_cookie($user->ID, true);
        do_action('wp_login', $user->user_login, $user);

        $redirect_url = apply_filters('slr_social_login_redirect_url', home_url('/my-account/'), $user, $provider);
        wp_redirect($redirect_url);
        exit;
    }
}