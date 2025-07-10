<?php
/**
 * Interface for all Bot API handlers.
 *
 * @package    Sms_Login_Register
 * @subpackage Sms_Login_Register/includes/integrations
 * @since      1.4.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

interface SLR_Bot_Handler_Interface {

    /**
     * Checks if the integration is properly configured and enabled.
     * @return bool
     */
    public function is_active(): bool;

    /**
     * Gets information about the bot itself (used for testing tokens).
     * @return array|WP_Error
     */
    public function get_me();

    /**
     * Sends a text message to a specific chat.
     * @param int|string $chat_id
     * @param string $text
     * @return array|WP_Error
     */
    public function send_message($chat_id, string $text);
    
    /**
     * Sends a message with a button prompting the user to share their contact info.
     * @param int|string $chat_id
     * @param string $text
     * @return array|WP_Error
     */
    public function request_contact_info($chat_id, string $text);

    /**
     * Sets the webhook URL for the bot.
     * @param string $webhook_url
     * @return array|WP_Error
     */
    public function set_webhook(string $webhook_url);

    /**
     * Gets the current webhook information.
     * @return array|WP_Error
     */
    public function get_webhook_info();
}