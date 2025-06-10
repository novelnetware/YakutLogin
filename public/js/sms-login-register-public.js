jQuery(document).ready(function($) {
    'use strict';

    // Helper function to validate email
    function isValidEmail(email) {
        var emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    }

    // --- Send OTP Handler ---
    // For generic OTP forms (shortcode, widget) and wp-login.php forms
    // Uses data-attributes on the button if available, otherwise specific IDs.
    $('body').on('click', '.slr-send-otp-button, .slr-send-otp-button-generic', function(e) {
        e.preventDefault();
        var $button = $(this);
        var $formContainer = $button.closest('.slr-otp-form-container, .slr-otp-wp-login-section, .slr-otp-wp-register-section');
        var $phoneField = $formContainer.find('.slr-phone-input').first();
        var phone = $phoneField.length ? $phoneField.val() : '';
        
        var emailFieldSelector = $button.data('email-field') || $formContainer.find('.slr-email-input').first();
        var $emailField = $(emailFieldSelector);
        if ($emailField.length === 0 && $formContainer.find('.slr-email-input').length > 0) {
             $emailField = $formContainer.find('.slr-email-input').first(); // fallback for generic form
        } else if ($emailField.length === 0 && $button.attr('id') === 'slr-send-otp-button-login-wp') {
            $emailField = $('#user_login'); // wp-login specific
        } else if ($emailField.length === 0 && $button.attr('id') === 'slr-send-otp-button-register-wp') {
            $emailField = $('#user_email'); // wp-register specific
        }


        var $messageDiv = $button.data('message-target') ? $($button.data('message-target')) : $formContainer.find('.slr-message-area').first();
        // var nonce = $formContainer.find('input[name="slr_send_otp_nonce_field"]').val() || slr_public_data.send_otp_nonce;
        // The nonce for send_otp is globally available via slr_public_data.send_otp_nonce

        var email = $emailField.val();

        $messageDiv.text(slr_public_data.text_sending_otp).removeClass('slr-error slr-success').addClass('slr-info');

        if (!email || !isValidEmail(email)) {
            $messageDiv.text(slr_public_data.text_invalid_email).addClass('slr-error');
            return;
        }

        $button.prop('disabled', true);

        $.ajax({
            url: slr_public_data.ajax_url,
            type: 'POST',
            data: {
                action: 'slr_send_otp',
                email: email,
                phone: phone,
                security: slr_public_data.send_otp_nonce
            },
            success: function(response) {
                if (response.success) {
                    $messageDiv.text(response.data.message || slr_public_data.text_otp_sent).addClass('slr-success');
                    $formContainer.find('.slr-otp-row, .slr-submit-row').slideDown(); // Show OTP and submit fields
                } else {
                    $messageDiv.text(response.data.message || slr_public_data.text_error_sending_otp).addClass('slr-error');
                }
            },
            error: function() {
                $messageDiv.text(slr_public_data.text_error_sending_otp).addClass('slr-error');
            },
            complete: function() {
                setTimeout(function() { $button.prop('disabled', false); }, 2000);
            }
        });
    });

    // --- Process Login/Register Form Submission Handler (for generic forms) ---
    $('body').on('submit', 'form.slr-otp-form', function(e) {
        e.preventDefault();
        var $form = $(this);
        var $messageDiv = $form.find('.slr-message-area');
        var $submitButton = $form.find('.slr-submit-button');

        var email = $form.find('.slr-email-input').val();
        var otp = $form.find('.slr-otp-input').val();
        var processNonce = $form.find('input[name="slr_process_form_nonce_field"]').val();
        var redirectTo = $form.find('input[name="slr_redirect_to"]').val() || '';


        $messageDiv.text(slr_public_data.text_processing).removeClass('slr-error slr-success').addClass('slr-info');
        $submitButton.prop('disabled', true);

        if (!otp) {
            $messageDiv.text(slr_public_data.text_fill_otp).addClass('slr-error');
            $submitButton.prop('disabled', false);
            return;
        }

        $.ajax({
            url: slr_public_data.ajax_url,
            type: 'POST',
            data: {
                action: 'slr_process_login_register_otp',
                email: email,
                otp_code: otp,
                slr_process_form_nonce_field: processNonce, // Nonce for this specific action
                redirect_to: redirectTo,
                // context: $form.find('input[name="slr_form_context"]').val() // if needed by backend
            },
            success: function(response) {
                if (response.success) {
                    $messageDiv.text(response.data.message).addClass('slr-success');
                    if (response.data.redirect_url) {
                        window.location.href = response.data.redirect_url;
                    } else {
                         // Fallback if no redirect URL, maybe refresh or show specific message.
                        // For now, just disable button on success to prevent resubmit.
                    }
                } else {
                    $messageDiv.text(response.data.message || 'An error occurred.').addClass('slr-error');
                    $submitButton.prop('disabled', false);
                }
            },
            error: function() {
                $messageDiv.text('A critical error occurred. Please try again.').addClass('slr-error');
                $submitButton.prop('disabled', false);
            },
            // complete: function() { // Button re-enabled on error explicitly above }
        });
    });
    
    // Basic styling (add to your public CSS file)
    var styles = `
        .slr-message-area { margin-top: 10px; padding: 8px; border-radius: 3px; }
        .slr-message-area.slr-error { color: #721c24; background-color: #f8d7da; border: 1px solid #f5c6cb; }
        .slr-message-area.slr-success { color: #155724; background-color: #d4edda; border: 1px solid #c3e6cb; }
        .slr-message-area.slr-info { color: #0c5460; background-color: #d1ecf1; border: 1px solid #bee5eb; }
        .slr-otp-row, .slr-submit-row { margin-top: 10px; }
    `;
    if (!$('style#slr-dynamic-styles').length) {
        $('head').append('<style id="slr-dynamic-styles">' + styles + '</style>');
    }
});