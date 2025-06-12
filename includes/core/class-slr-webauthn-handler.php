<?php

use Webauthn\PublicKeyCredentialCreationOptions;
use Webauthn\PublicKeyCredentialRequestOptions;
use Webauthn\PublicKeyCredentialSourceRepository;
use Webauthn\PublicKeyCredentialSource;
use Webauthn\PublicKeyCredentialUserEntity;
use Webauthn\PublicKeyCredentialParameters;
use Webauthn\PublicKeyCredentialDescriptor;
use Webauthn\RelyingParty;
use Webauthn\Server;
use Webauthn\PublicKeyCredentialLoader;

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

/**
 * Handles all WebAuthn related logic (registration and authentication).
 */
class SLR_WebAuthn_Handler implements PublicKeyCredentialSourceRepository {

    private $server;

    public function __construct() {
        // ۱. تعریف اطلاعات وبسایت (Relying Party)
        $rpEntity = new RelyingParty(
            get_bloginfo('name'), // نام وبسایت شما
            wp_parse_url(get_site_url(), PHP_URL_HOST), // دامنه اصلی سایت
            get_site_url() . '/?slr-webauthn-icon=1' // آدرس آیکون (اختیاری)
        );

        // ۲. مقداردهی سرور WebAuthn با اطلاعات سایت و این کلاس به عنوان منبع داده
        $this->server = new Server(
            $rpEntity,
            $this, // این کلاس وظیفه ارتباط با دیتابیس را بر عهده دارد
            ['sha256'] // الگوریتم‌های پشتیبانی شده
        );
    }
    
    /**
     * AJAX handler to generate authentication options.
     */
    public function ajax_get_authentication_options() {
        check_ajax_referer('yakutlogin_admin_nonce', 'nonce'); // از همان نانس ادمین برای سادگی استفاده می‌کنیم
        
        $identifier_input = isset($_POST['identifier']) ? sanitize_text_field($_POST['identifier']) : '';
        if (empty($identifier_input)) {
            wp_send_json_error(['message' => 'لطفا شناسه کاربری (ایمیل/تلفن) را وارد کنید.']);
        }
        
        // پیدا کردن کاربر بر اساس شناسه
        $user = is_email($identifier_input) ? get_user_by('email', $identifier_input) : null;
        if (!$user) {
            // منطق پیدا کردن کاربر با شماره تلفن را اینجا اضافه کنید اگر لازم است
            wp_send_json_error(['message' => 'کاربری با این شناسه یافت نشد.']);
        }
        
        $userEntity = new PublicKeyCredentialUserEntity($user->user_email, (string) $user->ID, $user->display_name);
        $allowedCredentials = $this->findAllForUserEntity($userEntity);
        
        if (empty($allowedCredentials)) {
            wp_send_json_error(['message' => 'هیچ دستگاهی برای این کاربر ثبت نشده است.']);
        }

        $requestOptions = $this->server->generatePublicKeyCredentialRequestOptions(
            PublicKeyCredentialRequestOptions::USER_VERIFICATION_REQUIREMENT_PREFERRED,
            $allowedCredentials
        );

        set_transient('webauthn_request_options_' . $user->ID, $requestOptions, 5 * MINUTE_IN_SECONDS);

        wp_send_json_success($requestOptions);
    }

   /**
     * AJAX handler to verify the authentication data.
     */
    public function ajax_verify_authentication() {
        check_ajax_referer('yakutlogin_admin_nonce', 'nonce');
        
        try {
            $identifier_input = isset($_POST['identifier']) ? sanitize_text_field($_POST['identifier']) : '';
            $user = is_email($identifier_input) ? get_user_by('email', $identifier_input) : null;
            
            if (!$user) {
                wp_send_json_error(['message' => 'کاربر یافت نشد.']);
            }

            $publicKeyCredentialSource = $this->server->loadAndCheckAssertionResponse(
                file_get_contents('php://input'),
                get_transient('webauthn_request_options_' . $user->ID)
            );
            
            // به‌روزرسانی شمارنده امضا برای جلوگیری از حملات تکرار
            $this->saveCredentialSource($publicKeyCredentialSource);
            
            delete_transient('webauthn_request_options_' . $user->ID);

            // ورود کاربر به وردپرس
            wp_clear_auth_cookie();
            wp_set_current_user($user->ID);
            wp_set_auth_cookie($user->ID, true);
            do_action('wp_login', $user->user_login, $user);

            $redirect_url = apply_filters('slr_login_redirect_url_default', admin_url(), $user);
            
            wp_send_json_success([
                'message' => 'ورود موفقیت‌آمیز بود!',
                'redirect_url' => $redirect_url
            ]);

        } catch (Throwable $e) {
            wp_send_json_error(['message' => 'خطا در احراز هویت: ' . $e->getMessage()]);
        }
    }

    /* =========================================================================
     * متدهای مربوط به اینترفیس PublicKeyCredentialSourceRepository
     * این متدها وظیفه ارتباط با دیتابیس سفارشی ما را دارند.
     * ========================================================================= */

    public function findOneByCredentialId(string $publicKeyCredentialId): ?PublicKeyCredentialSource {
        global $wpdb;
        $table = $wpdb->prefix . 'slr_webauthn_credentials';
        $data = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE credential_id = %s", base64_encode($publicKeyCredentialId)), ARRAY_A);

        if (!$data) {
            return null;
        }

        return PublicKeyCredentialSource::createFromArray($data);
    }

    public function findAllForUserEntity(PublicKeyCredentialUserEntity $publicKeyCredentialUserEntity): array {
        global $wpdb;
        $table = $wpdb->prefix . 'slr_webauthn_credentials';
        $results = [];
        $rows = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$table} WHERE user_id = %d", $publicKeyCredentialUserEntity->getId()), ARRAY_A);

        foreach ($rows as $row) {
            $results[] = PublicKeyCredentialSource::createFromArray($row);
        }

        return $results;
    }

    public function saveCredentialSource(PublicKeyCredentialSource $publicKeyCredentialSource): void {
        global $wpdb;
        $table = $wpdb->prefix . 'slr_webauthn_credentials';
        
        $data = $publicKeyCredentialSource->jsonSerialize();

        $wpdb->insert($table, [
            'user_id'            => $data['userHandle'],
            'credential_id'      => $data['publicKeyCredentialId'],
            'public_key'         => $data['credentialPublicKey'],
            'signature_counter'  => $data['counter'],
        ]);
    }
}