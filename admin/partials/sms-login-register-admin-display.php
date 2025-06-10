<?php
/**
 * Provide a admin area view for the plugin
 *
 * This file is used to markup the admin-facing aspects of the plugin.
 *
 * @link       https://example.com/
 * @since      1.0.0
 *
 * @package    Sms_Login_Register
 * @subpackage Sms_Login_Register/admin/partials
 */
?>

<div class="wrap">
    <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
    <form method="post" action="options.php">
        <?php
        // This prints out all hidden Efields
        settings_fields( 'slr_option_group' ); // Must match the group name in register_setting()
        do_settings_sections( $this->plugin_name . '-settings' ); // Must match the page slug
        submit_button();
        ?>
    </form>
</div>