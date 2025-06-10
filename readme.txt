=== SMS Login & Register ===
Contributors: (your-wordpress-org-username), yourname
Donate link: https://example.com/donate/
Tags: sms login, otp login, sms registration, otp, mobile login, google login, recaptcha, turnstile, woocommerce login, elementor login
Requires at least: 5.5
Tested up to: 6.5  // یا آخرین نسخه وردپرس در زمان انتشار
Requires PHP: 7.4
Stable tag: 1.0.4 // نسخه‌ای که پایدار می‌دانید و در حال انتشار آن هستید
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A comprehensive WordPress plugin for SMS & Email OTP based login/registration, Google Login, CAPTCHA integration, and customizable forms via Shortcode & Elementor.

== Description ==

The "SMS Login & Register" plugin enhances your WordPress site's authentication system by providing multiple ways for users to log in and register. It supports:

* **OTP (One-Time Password) via SMS:** Integrate with various SMS gateways (Kavenegar included as an example) to send OTPs to users' mobile numbers.
* **OTP via Email:** As an alternative or fallback, send OTPs to users' email addresses.
* **Google Login:** Allow users to quickly log in or register using their Google accounts (OAuth 2.0).
* **CAPTCHA Protection:** Secure your forms using Google reCAPTCHA v2 ("I'm not a robot") or Cloudflare Turnstile.
* **Customizable Forms:**
    * **Shortcode:** Display the login/registration form anywhere using the `[slr_otp_form]` shortcode with various attributes for layout and theming.
    * **Elementor Widget:** A dedicated Elementor widget to drag, drop, and extensively customize the form's appearance and layout.
* **WooCommerce Integration:** Option to replace the default WooCommerce login and registration prompts on the checkout page with the OTP system.
* **Theme & Layout Options:** Provides basic themes and layout options for the forms.
* **Iranian Phone Number Support:** Includes basic normalization for Iranian phone numbers.

This plugin aims to provide a secure, flexible, and user-friendly authentication experience for your WordPress site.

== Installation ==

1.  Upload the `sms-login-register` folder to the `/wp-content/plugins/` directory.
    OR
    Upload the plugin zip file through the 'Plugins' > 'Add New' > 'Upload Plugin' screen in your WordPress admin area.
2.  Activate the plugin through the 'Plugins' screen in WordPress.
3.  Navigate to "SMS Login" (or your chosen menu name) in the WordPress admin menu to configure the settings:
    * Select and configure your SMS Gateway (e.g., Kavenegar API key).
    * Set up Email OTP templates.
    * Configure Google Login (Client ID, Client Secret from Google Cloud Console).
    * Set up CAPTCHA (reCAPTCHA or Turnstile Site Key and Secret Key).
    * Enable WooCommerce checkout integration if needed.
4.  Use the `[slr_otp_form]` shortcode in your posts/pages or the "SMS/OTP Login Form" widget in Elementor to display the login/registration form.

== Frequently Asked Questions ==

= How do I get API keys for SMS Gateways? =

You need to register an account with your chosen SMS provider (e.g., Kavenegar.com). They will provide you with an API key and potentially a sender line number or template name for OTPs. Enter these details in the plugin's settings page.

= How do I set up Google Login? =

1.  Go to the [Google Cloud Console](https://console.cloud.google.com/).
2.  Create a new project or select an existing one.
3.  Go to "APIs & Services" > "Credentials".
4.  Click "Create Credentials" > "OAuth client ID". Select "Web application".
5.  Under "Authorized redirect URIs", add the URI displayed in the plugin's Google Login settings (e.g., `https://yourdomain.com/?slr_google_auth_callback=1`).
6.  Copy the generated "Client ID" and "Client Secret" and paste them into the plugin's settings.
7.  Ensure the "Google People API" (or similar providing email/profile scope) is enabled for your project in the "Library" section.

= How do I set up CAPTCHA? =

1.  **For Google reCAPTCHA v2 ("I'm not a robot"):**
    * Go to the [Google reCAPTCHA admin console](https://www.google.com/recaptcha/admin/).
    * Register your site, choosing "reCAPTCHA v2" and then "I'm not a robot" Checkbox.
    * Add your domain(s).
    * Copy the "Site Key" and "Secret Key" into the plugin's CAPTCHA settings.
2.  **For Cloudflare Turnstile:**
    * Log in to your Cloudflare dashboard.
    * Navigate to "Turnstile".
    * Add a new site, get your "Site Key" and "Secret Key", and enter them into the plugin's settings.

= Can I customize the form's appearance? =

Yes!
* **Elementor Widget:** The "SMS/OTP Login Form" widget provides extensive styling controls in the Elementor editor's "Style" tab, as well as layout and theme selectors in the "Content" tab.
* **Shortcode:** The `[slr_otp_form]` shortcode accepts attributes like `theme="minimal"`, `layout="compact"`, and `text_send_otp="ارسال کد"` to customize the look and button texts.
* **Custom CSS:** You can always add your own custom CSS to further style the form elements. The main container has the class `slr-otp-form-container`.

== Screenshots ==

1.  Plugin Settings Page - General Settings.
2.  Plugin Settings Page - SMS Gateway Configuration (e.g., Kavenegar).
3.  Plugin Settings Page - Google Login Configuration.
4.  Plugin Settings Page - CAPTCHA Configuration.
5.  Example of the OTP form displayed via Shortcode (Default Theme).
6.  Example of the OTP form customized with the Elementor widget.
7.  OTP form integrated into WooCommerce Checkout.

(You will need to create these screenshots yourself)

== Changelog ==

= 1.0.4 (Current Version - Based on our progress) =
* Fix: Save phone number correctly on user registration via wp-login.php OTP using a transient and `user_register` hook.
* Enhancement: Added `layout` and `button_texts` options to `get_otp_form_html` for more flexible form rendering.
* Enhancement: Updated shortcode to support `layout` and custom button text attributes.
* Enhancement: Added layout selector, button text controls, and more granular styling controls (spacing, labels) to the Elementor widget.
* Enhancement: Added basic CSS for new form layouts (compact, inline_labels).
* Fix: Corrected `wp_localize_script` call in `maybe_enqueue_scripts`.
* Refactor: Improved OTP identifier logic in `authenticate_with_otp` and `validate_registration_with_otp` for wp-login.php forms.
* Feature: Added CAPTCHA display and verification to `wp-login.php` forms.

= 1.0.3 =
* Feature: Integrated CAPTCHA (Google reCAPTCHA v2 and Cloudflare Turnstile).
* Enhancement: Added CAPTCHA settings to admin page.
* Enhancement: Created `SLR_Captcha_Handler` for server-side verification.
* Enhancement: Display CAPTCHA widget on forms and integrated verification into AJAX handlers.

= 1.0.2 =
* Feature: Integrated Google Login (OAuth 2.0).
* Feature: Implemented SMS gateway abstraction and Kavenegar as the first example provider.
* Enhancement: Added settings for Google Login and SMS providers.
* Enhancement: Added phone field to forms and updated AJAX handlers for SMS OTP.
* Enhancement: Basic Iranian phone number normalization.

= 1.0.1 =
* Feature: Created generic OTP form HTML generator (`get_otp_form_html`).
* Feature: Implemented `[slr_otp_form]` shortcode.
* Feature: Basic Elementor widget structure for the OTP form.
* Feature: New AJAX handler (`ajax_process_login_register_otp`) for generic form submissions.
* Enhancement: Refactored JavaScript and script enqueueing for flexibility.
* Enhancement: Added basic theming (CSS classes) to OTP form.

= 1.0.0 =
* Initial release.
* Basic plugin structure.
* OTP generation, storage (hashed), and verification logic.
* Email OTP sending.
* AJAX handler for sending email OTP.
* Integration with WordPress default login/registration forms (OTP field, "Send OTP" button, verification via `authenticate` and `registration_errors` hooks).
* Admin settings page using WordPress Settings API for basic options.

== Upgrade Notice ==

= 1.0.4 =
This version includes significant enhancements to form customization via shortcode and Elementor, and fixes phone number saving for wp-login.php registrations. Please review your Elementor widget settings and shortcode attributes if you were using custom layouts previously, as the implementation has been formalized.

(Add other upgrade notices as needed for future versions)