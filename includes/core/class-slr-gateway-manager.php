<?php
/**
 * Manages SMS Gateways.
 *
 * @since 1.5.1 (Refactored for Fallback Gateway)
 */
class SLR_Gateway_Manager {

    private $gateways = array();
    private $options = array();

    private $primary_gateway_instance = null;
    private $backup_gateway_instance = null;

    public function __construct() {
        $this->options = get_option( 'slr_plugin_options', array() );
        $this->load_gateways();
        $this->set_active_gateways(); // نام متد تغییر کرد
    }

    /**
     * Loads available gateway classes.
     * It's crucial to load the interface before the classes that implement it.
     */
    private function load_gateways() {
        // Load the interface first, so all gateways can implement it.
        require_once SLR_PLUGIN_DIR . 'includes/gateways/interface-slr-sms-gateway.php';
    
        // Now, load the individual gateway classes.
        // Kavenegar
        if ( file_exists( SLR_PLUGIN_DIR . 'includes/gateways/class-slr-kavenegar-gateway.php' ) ) {
            require_once SLR_PLUGIN_DIR . 'includes/gateways/class-slr-kavenegar-gateway.php';
            if ( class_exists( 'SLR_Kavenegar_Gateway' ) ) {
                $kavenegar = new SLR_Kavenegar_Gateway();
                $this->gateways[$kavenegar->get_id()] = $kavenegar;
            }
        }
    
        // MeliPayamak
        if ( file_exists( SLR_PLUGIN_DIR . 'includes/gateways/class-slr-melipayamak-gateway.php' ) ) {
            require_once SLR_PLUGIN_DIR . 'includes/gateways/class-slr-melipayamak-gateway.php';
            if ( class_exists( 'SLR_MeliPayamak_Gateway' ) ) {
                $melipayamak = new SLR_MeliPayamak_Gateway();
                $this->gateways[$melipayamak->get_id()] = $melipayamak;
            }
        }
    
        // Kavan SMS
        if ( file_exists( SLR_PLUGIN_DIR . 'includes/gateways/class-slr-kavansms-gateway.php' ) ) {
            require_once SLR_PLUGIN_DIR . 'includes/gateways/class-slr-kavansms-gateway.php';
            if ( class_exists( 'SLR_KavanSms_Gateway' ) ) {
                $kavansms = new SLR_KavanSms_Gateway();
                $this->gateways[$kavansms->get_id()] = $kavansms;
            }
        }
    
        // Faraz SMS
        if ( file_exists( SLR_PLUGIN_DIR . 'includes/gateways/class-slr-farazsms-gateway.php' ) ) {
            require_once SLR_PLUGIN_DIR . 'includes/gateways/class-slr-farazsms-gateway.php';
            if ( class_exists( 'SLR_FarazSms_Gateway' ) ) {
                $farazsms = new SLR_FarazSms_Gateway();
                $this->gateways[$farazsms->get_id()] = $farazsms;
            }
        }
    
        // SMS.ir
        if ( file_exists( SLR_PLUGIN_DIR . 'includes/gateways/class-slr-smsir-gateway.php' ) ) {
            require_once SLR_PLUGIN_DIR . 'includes/gateways/class-slr-smsir-gateway.php';
            if ( class_exists( 'SLR_SmsIr_Gateway' ) ) {
                $smsir = new SLR_SmsIr_Gateway();
                $this->gateways[$smsir->get_id()] = $smsir;
            }
        }
    
        $this->gateways = apply_filters( 'slr_register_sms_gateways', $this->gateways );
    }

   /**
     * Sets the primary and backup gateway instances based on plugin settings.
     */
    private function set_active_gateways() {
        $primary_id = $this->options['sms_provider'] ?? null;
        $backup_id  = $this->options['sms_provider_backup'] ?? null;

        if ( $primary_id && isset( $this->gateways[$primary_id] ) ) {
            $this->primary_gateway_instance = $this->gateways[$primary_id];
        }

        if ( $backup_id && isset( $this->gateways[$backup_id] ) && $backup_id !== $primary_id ) {
            $this->backup_gateway_instance = $this->gateways[$backup_id];
        }
    }

    public function get_available_gateways() {
        return $this->gateways;
    }

    /**
     * Returns the currently active primary gateway instance.
     * @return SLR_Sms_Gateway|null
     */
    public function get_active_gateway() {
        return $this->primary_gateway_instance;
    }
    
    /**
     * Sends an SMS using the active gateway, with a fallback to the backup gateway.
     *
     * @param string $phone_number The recipient's phone number.
     * @param string $otp_code The OTP code.
     * @return bool True on success, false on failure.
     */
    public function send_otp( $phone_number, $otp_code ) {
        // ۱. بررسی وجود درگاه اصلی
        if ( ! $this->primary_gateway_instance ) {
            error_log('YakutLogin Error: No primary SMS gateway is configured.');
            return false;
        }

        // نرمال‌سازی شماره تلفن
        $normalized_phone = $this->normalize_iranian_phone($phone_number);
        if (!$normalized_phone) {
            error_log('YakutLogin Error: Invalid phone number format for SMS OTP.');
            return false;
        }
        
        $message_template = $this->options['sms_otp_template'] ?? "کد تایید شما: {otp_code}";
        $message = str_replace('{otp_code}', $otp_code, $message_template);

        // ۲. تلاش برای ارسال با درگاه اصلی
        $sent_successfully = $this->primary_gateway_instance->send_sms( $normalized_phone, $message, $otp_code );

        // ۳. اگر موفق بود، نتیجه را برگردان
        if ( $sent_successfully ) {
            return true;
        }

        // ۴. اگر ناموفق بود و درگاه پشتیبان وجود داشت، با آن تلاش کن
        error_log('YakutLogin Fallback: Primary gateway (' . $this->primary_gateway_instance->get_name() . ') failed. Attempting backup.');

        if ( ! $this->backup_gateway_instance ) {
            error_log('YakutLogin Fallback: No backup gateway configured.');
            return false; // ارسال ناموفق بود و پشتیبانی هم وجود ندارد
        }
        
        // ۵. تلاش برای ارسال با درگاه پشتیبان
        return $this->backup_gateway_instance->send_sms( $normalized_phone, $message, $otp_code );
    }

    /**
     * Normalizes an Iranian phone number.
     *
     * @param string $phone
     * @return string|false Normalized phone number or false if invalid pattern.
     */
    public function normalize_iranian_phone( $phone ) {
        $phone = preg_replace( '/[^0-9]/', '', $phone );

        if ( substr( $phone, 0, 1 ) === '0' && strlen( $phone ) === 11 && substr( $phone, 0, 2 ) === '09') {
            return '+98' . substr( $phone, 1 );
        }
        if (strlen( $phone ) === 10 && substr( $phone, 0, 1 ) === '9') {
            return '+98' . $phone;
        }
        if (substr( $phone, 0, 3 ) === '989' && strlen( $phone ) === 12 ) {
            return '+' . $phone;
        }
        if (substr( $phone, 0, 4) === '+989' && strlen( $phone ) === 13) {
            return $phone;
        }
        
        return false;
    }
}