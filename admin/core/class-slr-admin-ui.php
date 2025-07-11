<?php
/**
 * Handles the admin-facing UI/UX of the plugin, including menus, scripts, and pages.
 *
 * @package     Sms_Login_Register
 * @subpackage  Sms_Login_Register/admin/core
 * @since       1.4.0
 */
if ( ! defined( 'ABSPATH' ) ) exit;

class SLR_Admin_UI {

    private $plugin_name;
    private $version;
    private $gateway_manager;

    public function __construct($plugin_name, $version) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
        if (class_exists('SLR_Gateway_Manager')) {
            $this->gateway_manager = new SLR_Gateway_Manager();
        }
    }

    /**
     * Register all hooks related to the admin UI.
     */
    public function init_hooks() {
        add_action( 'admin_menu', [ $this, 'add_plugin_admin_menu' ] );
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_styles_and_scripts' ] );
    }

    /**
     * Add the main plugin menu to the WordPress admin dashboard.
     */
    public function add_plugin_admin_menu() {
        $icon_svg_base64 = 'data:image/svg+xml;base64,PD94bWwgdmVyc2lvbj0iMS4wIiBlbmNvZGluZz0iVVRGLTgiPz4KPHN2ZyBpZD0iTE9HTyIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIiB4bWxuczp4bGluaz0iaHR0cDovL3d3dy53My5vcmcvMTk5OS94bGluayIgdmlld0JveD0iMCAwIDE3NjAuMiAxMDY5Ljk4Ij4KICA8ZGVmcz4KICAgIDxzdHlsZT4KICAgICAgLmNscy0xIHsKICAgICAgICBtYXNrOiB1cmwoI21hc2stNSk7CiAgICAgIH0KCiAgICAgIC5jbHMtMiB7CiAgICAgICAgZmlsbDogdXJsKCNsaW5lYXItZ3JhZGllbnQtMik7CiAgICAgIH0KCiAgICAgIC5jbHMtMiwgLmNscy0zIHsKICAgICAgICBtaXgtYmxlbmQtbW9kZTogbXVsdGlwbHk7CiAgICAgIH0KCiAgICAgIC5jbHMtNCB7CiAgICAgICAgbWFzazogdXJsKCNtYXNrLTEpOwogICAgICB9CgogICAgICAuY2xzLTUgewogICAgICAgIG1hc2s6IHVybCgjbWFzayk7CiAgICAgIH0KCiAgICAgIC5jbHMtNiB7CiAgICAgICAgbWFzazogdXJsKCNtYXNrLTIpOwogICAgICB9CgogICAgICAuY2xzLTcgewogICAgICAgIGZpbHRlcjogdXJsKCNsdW1pbm9zaXR5LW5vY2xpcC0zKTsKICAgICAgfQoKICAgICAgLmNscy04IHsKICAgICAgICBmaWxsOiB1cmwoI2xpbmVhci1ncmFkaWVudC0zKTsKICAgICAgfQoKICAgICAgLmNscy05IHsKICAgICAgICBmaWxsOiB1cmwoI2xpbmVhci1ncmFkaWVudC01KTsKICAgICAgfQoKICAgICAgLmNscy0zIHsKICAgICAgICBmaWxsOiB1cmwoI2xpbmVhci1ncmFkaWVudC00KTsKICAgICAgfQoKICAgICAgLmNscy0xMCB7CiAgICAgICAgZmlsbDogcmdiYSgyNTAsIDIxMSwgMTI5LCAuMik7CiAgICAgIH0KCiAgICAgIC5jbHMtMTEgewogICAgICAgIGZpbGw6IHJnYmEoNjAsIDIxOSwgODIsIC4yKTsKICAgICAgfQoKICAgICAgLmNscy0xMiB7CiAgICAgICAgbWFzazogdXJsKCNtYXNrLTQpOwogICAgICB9CgogICAgICAuY2xzLTEzIHsKICAgICAgICBmaWxsOiByZ2JhKDY1LCA2NCwgMTcsIC4xNSk7CiAgICAgIH0KCiAgICAgIC5jbHMtMTQgewogICAgICAgIGZpbGw6ICMyYjJiMDU7CiAgICAgIH0KCiAgICAgIC5jbHMtMTUgewogICAgICAgIGZpbGw6IHVybCgjbGluZWFyLWdyYWRpZW50KTsKICAgICAgfQoKICAgICAgLmNscy0xNiB7CiAgICAgICAgZmlsbDogcmdiYSgxMzAsIDIyOCwgOTgsIC4yKTsKICAgICAgfQoKICAgICAgLmNscy0xNyB7CiAgICAgICAgbWFzazogdXJsKCNtYXNrLTMpOwogICAgICB9CgogICAgICAuY2xzLTE4IHsKICAgICAgICBmaWx0ZXI6IHVybCgjbHVtaW5vc2l0eS1ub2NsaXApOwogICAgICB9CiAgICA8L3N0eWxlPgogICAgPGxpbmVhckdyYWRpZW50IGlkPSJsaW5lYXItZ3JhZGllbnQiIHgxPSI4ODAuMSIgeTE9Ii0zNTcuNjgiIHgyPSI4ODAuMSIgeTI9IjEzNTkuOTgiIGdyYWRpZW50VHJhbnNmb3JtPSJ0cmFuc2xhdGUoMCAxMjAzLjM3KSBzY2FsZSgxIC0xKSIgZ3JhZGllbnRVbml0cz0idXNlclNwYWNlT25Vc2UiPgogICAgICA8c3RvcCBvZmZzZXQ9IjAiIHN0b3AtY29sb3I9IiM1MjBjMTMiLz4KICAgICAgPHN0b3Agb2Zmc2V0PSIxIiBzdG9wLWNvbG9yPSIjM2FjNjFjIi8+CiAgICA8L2xpbmVhckdyYWRpZW50PgogICAgPGZpbHRlciBpZD0ibHVtaW5vc2l0eS1ub2NsaXAiIHg9Ijc0LjM1IiB5PSI0NTYuNDMiIHdpZHRoPSI0ODcuNiIgaGVpZ2h0PSIyNDYuMDUiIGNvbG9yLWludGVycG9sYXRpb24tZmlsdGVycz0ic1JHQiIgZmlsdGVyVW5pdHM9InVzZXJTcGFjZU9uVXNlIj4KICAgICAgPGZlRmxvb2QgZmxvb2QtY29sb3I9IiNmZmYiIHJlc3VsdD0iYmciLz4KICAgICAgPGZlQmxlbmQgaW49IlNvdXJjZUdyYXBoaWMiIGluMj0iYmciLz4KICAgIDwvZmlsdGVyPgogICAgPGZpbHRlciBpZD0ibHVtaW5vc2l0eS1ub2NsaXAtMiIgeD0iNzQuMzUiIHk9Ii04NzkyLjY5IiB3aWR0aD0iNDg3LjYiIGhlaWdodD0iMzI3NjYiIGNvbG9yLWludGVycG9sYXRpb24tZmlsdGVycz0ic1JHQiIgZmlsdGVyVW5pdHM9InVzZXJTcGFjZU9uVXNlIj4KICAgICAgPGZlRmxvb2QgZmxvb2QtY29sb3I9IiNmZmYiIHJlc3VsdD0iYmciLz4KICAgICAgPGZlQmxlbmQgaW49IlNvdXJjZUdyYXBoaWMiIGluMj0iYmciLz4KICAgIDwvZmlsdGVyPgogICAgPG1hc2sgaWQ9Im1hc2stMiIgeD0iNzQuMzUiIHk9Ii04NzkyLjY5IiB3aWR0aD0iNDg3LjYiIGhlaWdodD0iMzI3NjYiIG1hc2tVbml0cz0idXNlclNwYWNlT25Vc2UiLz4KICAgIDxsaW5lYXJHcmFkaWVudCBpZD0ibGluZWFyLWdyYWRpZW50LTIiIHgxPSIxMDcuMDciIHkxPSI4MzkuMzQiIHgyPSIzMzIuNjciIHkyPSI2MDkuMSIgZ3JhZGllbnRUcmFuc2Zvcm09InRyYW5zbGF0ZSgwIDEyMDMuMzcpIHNjYWxlKDEgLTEpIiBncmFkaWVudFVuaXRzPSJ1c2VyU3BhY2VPblVzZSI+CiAgICAgIDxzdG9wIG9mZnNldD0iMCIgc3RvcC1jb2xvcj0iI2ZmZiIvPgogICAgICA8c3RvcCBvZmZzZXQ9IjEiIHN0b3AtY29sb3I9IiMwMDAiLz4KICAgIDwvbGluZWFyR3JhZGllbnQ+CiAgICA8bWFzayBpZD0ibWFzay0xIiB4PSI3NC4zNSIgeT0iNDU2LjQzIiB3aWR0aD0iNDg3LjYiIGhlaWdodD0iMjQ2LjA1IiBtYXNrVW5pdHM9InVzZXJTcGFjZU9uVXNlIj4KICAgICAgPGcgY2xhc3M9ImNscy0xOCI+CiAgICAgICAgPGcgY2xhc3M9ImNscy02Ij4KICAgICAgICAgIDxyZWN0IGNsYXNzPSJjbHMtMiIgeD0iNzQuMzUiIHk9IjQ1Ni40MyIgd2lkdGg9IjQ4Ny42IiBoZWlnaHQ9IjI0Ni4wNSIvPgogICAgICAgIDwvZz4KICAgICAgPC9nPgogICAgPC9tYXNrPgogICAgPGxpbmVhckdyYWRpZW50IGlkPSJsaW5lYXItZ3JhZGllbnQtMyIgeDE9IjEwNy4wNyIgeTE9IjgzOS4zNCIgeDI9IjMzMi42NyIgeTI9IjYwOS4xIiBncmFkaWVudFRyYW5zZm9ybT0idHJhbnNsYXRlKDAgMTIwMy4zNykgc2NhbGUoMSAtMSkiIGdyYWRpZW50VW5pdHM9InVzZXJTcGFjZU9uVXNlIj4KICAgICAgPHN0b3Agb2Zmc2V0PSIwIiBzdG9wLWNvbG9yPSIjZmZmIi8+CiAgICAgIDxzdG9wIG9mZnNldD0iMSIgc3RvcC1jb2xvcj0iI2ZmZiIvPgogICAgPC9saW5lYXJHcmFkaWVudD4KICAgIDxtYXNrIGlkPSJtYXNrIiB4PSI3NC4zNSIgeT0iNDU2LjQzIiB3aWR0aD0iNDg3LjYiIGhlaWdodD0iMjQ2LjA1IiBtYXNrVW5pdHM9InVzZXJTcGFjZU9uVXNlIj4KICAgICAgPGcgaWQ9ImlkMCI+CiAgICAgICAgPGcgY2xhc3M9ImNscy00Ij4KICAgICAgICAgIDxyZWN0IGNsYXNzPSJjbHMtOCIgeD0iNzQuMzUiIHk9IjQ1Ni40MyIgd2lkdGg9IjQ4Ny42IiBoZWlnaHQ9IjI0Ni4wNSIvPgogICAgICAgIDwvZz4KICAgICAgPC9nPgogICAgPC9tYXNrPgogICAgPGZpbHRlciBpZD0ibHVtaW5vc2l0eS1ub2NsaXAtMyIgeD0iNjYuNjMiIHk9Ijk5Ljk4IiB3aWR0aD0iNDAwLjM0IiBoZWlnaHQ9IjE5MC4yNiIgY29sb3ItaW50ZXJwb2xhdGlvbi1maWx0ZXJzPSJzUkdCIiBmaWx0ZXJVbml0cz0idXNlclNwYWNlT25Vc2UiPgogICAgICA8ZmVGbG9vZCBmbG9vZC1jb2xvcj0iI2ZmZiIgcmVzdWx0PSJiZyIvPgogICAgICA8ZmVCbGVuZCBpbj0iU291cmNlR3JhcGhpYyIgaW4yPSJiZyIvPgogICAgPC9maWx0ZXI+CiAgICA8ZmlsdGVyIGlkPSJsdW1pbm9zaXR5LW5vY2xpcC00IiB4PSI2Ni42MyIgeT0iLTg3OTIuNjkiIHdpZHRoPSI0MDAuMzQiIGhlaWdodD0iMzI3NjYiIGNvbG9yLWludGVycG9sYXRpb24tZmlsdGVycz0ic1JHQiIgZmlsdGVyVW5pdHM9InVzZXJTcGFjZU9uVXNlIj4KICAgICAgPGZlRmxvb2QgZmxvb2QtY29sb3I9IiNmZmYiIHJlc3VsdD0iYmciLz4KICAgICAgPGZlQmxlbmQgaW49IlNvdXJjZUdyYXBoaWMiIGluMj0iYmciLz4KICAgIDwvZmlsdGVyPgogICAgPG1hc2sgaWQ9Im1hc2stNSIgeD0iNjYuNjMiIHk9Ii04NzkyLjY5IiB3aWR0aD0iNDAwLjM0IiBoZWlnaHQ9IjMyNzY2IiBtYXNrVW5pdHM9InVzZXJTcGFjZU9uVXNlIi8+CiAgICA8bGluZWFyR3JhZGllbnQgaWQ9ImxpbmVhci1ncmFkaWVudC00IiB4MT0iMTEyLjQ5IiB5MT0iNzkyLjI5IiB4Mj0iMzI1LjQ1IiB5Mj0iMTA5MC4zNSIgZ3JhZGllbnRUcmFuc2Zvcm09InRyYW5zbGF0ZSgwIDEyMDMuMzcpIHNjYWxlKDEgLTEpIiBncmFkaWVudFVuaXRzPSJ1c2VyU3BhY2VPblVzZSI+CiAgICAgIDxzdG9wIG9mZnNldD0iMCIgc3RvcC1jb2xvcj0iI2ZmZiIvPgogICAgICA8c3RvcCBvZmZzZXQ9IjEiIHN0b3AtY29sb3I9IiMwMDAiLz4KICAgIDwvbGluZWFyR3JhZGllbnQ+CiAgICA8bWFzayBpZD0ibWFzay00IiB4PSI2Ni42MyIgeT0iOTkuOTgiIHdpZHRoPSI0MDAuMzQiIGhlaWdodD0iMTkwLjI2IiBtYXNrVW5pdHM9InVzZXJTcGFjZU9uVXNlIj4KICAgICAgPGcgY2xhc3M9ImNscy03Ij4KICAgICAgICA8ZyBjbGFzcz0iY2xzLTEiPgogICAgICAgICAgPHJlY3QgY2xhc3M9ImNscy0zIiB4PSI2Ni42MyIgeT0iOTkuOTgiIHdpZHRoPSI0MDAuMzQiIGhlaWdodD0iMTkwLjI2Ii8+CiAgICAgICAgPC9nPgogICAgICA8L2c+CiAgICA8L21hc2s+CiAgICA8bGluZWFyR3JhZGllbnQgaWQ9ImxpbmVhci1ncmFkaWVudC01IiB4MT0iMTEyLjQ5IiB5MT0iNzkyLjI5IiB4Mj0iMzI1LjQ1IiB5Mj0iMTA5MC4zNSIgZ3JhZGllbnRUcmFuc2Zvcm09InRyYW5zbGF0ZSgwIDEyMDMuMzcpIHNjYWxlKDEgLTEpIiBncmFkaWVudFVuaXRzPSJ1c2VyU3BhY2VPblVzZSI+CiAgICAgIDxzdG9wIG9mZnNldD0iMCIgc3RvcC1jb2xvcj0iI2ZmZiIvPgogICAgICA8c3RvcCBvZmZzZXQ9IjEiIHN0b3AtY29sb3I9IiNmZmYiLz4KICAgIDwvbGluZWFyR3JhZGllbnQ+CiAgICA8bWFzayBpZD0ibWFzay0zIiB4PSI2Ni42MyIgeT0iOTkuOTgiIHdpZHRoPSI0MDAuMzQiIGhlaWdodD0iMTkwLjI2IiBtYXNrVW5pdHM9InVzZXJTcGFjZU9uVXNlIj4KICAgICAgPGcgaWQ9ImlkMiI+CiAgICAgICAgPGcgY2xhc3M9ImNscy0xMiI+CiAgICAgICAgICA8cmVjdCBjbGFzcz0iY2xzLTkiIHg9IjY2LjYzIiB5PSI5OS45OCIgd2lkdGg9IjQwMC4zNCIgaGVpZ2h0PSIxOTAuMjYiLz4KICAgICAgICA8L2c+CiAgICAgIDwvZz4KICAgIDwvbWFzaz4KICA8L2RlZnM+CiAgPHBvbHlnb24gY2xhc3M9ImNscy0xNSIgcG9pbnRzPSIyNzkuNzIgMCAwIDM4Mi4xIDY4Ny44OCAxMDY5Ljk4IDExODIuMDIgNTc1Ljg0IDk0MC40MyA1NzUuODQgNjg3Ljg4IDgyOC4zOSAzMTguMjQgNDU4Ljc3IDEyOTkuMTEgNDU4Ljc3IDE0NjkuOTQgMjg3LjkyIDI4MC4xOSAyODcuOTIgMzY1LjkxIDE3MC44MyAxNTg2LjI5IDE3MC44MyAxNzYwLjIgMCAyNzkuNzIgMCIvPgogIDxwYXRoIGNsYXNzPSJjbHMtMTMiIGQ9Ik02NDcuMjQsMjg3LjkyaDgyMi43bC0xNzAuODMsMTcwLjg1SDQ4NC4zNGM0Ny4wMi02My41LDEwMS43OS0xMjAuOTMsMTYyLjktMTcwLjg1Wk00MTkuMTQsNTU5LjY1bDI2OC43NCwyNjguNzQsMjUyLjU1LTI1Mi41NWgyNDEuNTlsLTQ5NC4xNCw0OTQuMTQtMzQyLjM4LTM0Mi4zOGMxOS4xOC01OC43LDQzLjkyLTExNC45LDczLjY0LTE2Ny45NWgwWk0xNjEwLjQ5LDE0Ny4wN2wtMjQuMiwyMy43NmgtNzU2LjY1YzEyNS43NS02MS42NywyNjcuMTMtOTYuMzUsNDE2LjY0LTk2LjM1LDEyOS4wOCwwLDI1Mi4wOCwyNS44NiwzNjQuMjEsNzIuNTlaIi8+CiAgPHBhdGggY2xhc3M9ImNscy0xMSIgZD0iTTI4MC4xOSwyODcuOTJoMjg0LjQxYzI0LjMyLDU0LjQ0LDQzLjY3LDExMS41Nyw1Ny40NywxNzAuODVoLTMwMy44NGwzMjIuMzcsMzIyLjM1Yy03LjY4LDY4LjMyLTIyLjU1LDEzNC40Ni00My45OSwxOTcuNkwwLDM4Mi4xLDI3OS43MiwwaDg1LjJjNTEuOTgsNTEuMzUsOTguMDQsMTA4LjY3LDEzNy4xMiwxNzAuODNoLTEzNi4xM2wtODUuNzIsMTE3LjA5aDBaIi8+CiAgPHBhdGggY2xhc3M9ImNscy0xMSIgZD0iTTgxNy42NywyODcuOTJoNjUyLjI3bC0xNzAuODMsMTcwLjg1aC0yMzguNTFjLTg5Ljg5LTQ0LjA4LTE3MS43OS0xMDEuOTMtMjQyLjkzLTE3MC44NVpNNjE0LjM1LDBoMTE0NS44NWwtMTczLjkxLDE3MC44M2gtODcxLjE5Yy0zOS4yNS01Mi45Ni03My4xLTExMC4xOS0xMDAuNzQtMTcwLjgzWiIvPgogIDxwb2x5Z29uIGNsYXNzPSJjbHMtMTYiIHBvaW50cz0iMTAuMjMgMzkyLjMyIDAgMzgyLjEgMjc5LjcyIDAgMTc2MC4yIDAgMTczNS41NyAyNC4yIDI3OS43MiAyNC4yIDg2LjY1IDI4Ny45MiAxNDY5Ljk0IDI4Ny45MiAxNDQ1LjczIDMxMi4xMiA2OC45NSAzMTIuMTIgMTAuMjMgMzkyLjMyIi8+CiAgPHBvbHlnb24gY2xhc3M9ImNscy0xMCIgcG9pbnRzPSI5NDAuNDMgNTc1Ljg0IDExODIuMDIgNTc1Ljg0IDExNTcuODIgNjAwLjA0IDk0MC40MyA2MDAuMDQgNjk5Ljk4IDg0MC40OSA2ODcuODggODI4LjM5IDk0MC40MyA1NzUuODQiLz4KICA8ZyBjbGFzcz0iY2xzLTUiPgogICAgPHBvbHlnb24gY2xhc3M9ImNscy0xNCIgcG9pbnRzPSIzMTguMjQgNDU4Ljc1IDU1OS42MyA3MDAuMTYgMzE4LjA2IDcwMC4xNiA3Ni42NyA0NTguNzUgMzE4LjI0IDQ1OC43NSIvPgogIDwvZz4KICA8ZyBjbGFzcz0iY2xzLTE3Ij4KICAgIDxwb2x5Z29uIGNsYXNzPSJjbHMtMTQiIHBvaW50cz0iNjguOTUgMjg3LjkyIDIwNC44MSAxMDIuMzEgNDE2LjA2IDEwMi4zMSA0NjQuNjQgMTcwLjgzIDM2NS42OSAxNzEuMTQgMjgwLjE5IDI4Ny45MiA2OC45NSAyODcuOTIiLz4KICA8L2c+Cjwvc3ZnPg==';
        add_menu_page(
            __( 'یاکوت لاگین', 'yakutlogin' ),
            __( 'یاکوت لاگین', 'yakutlogin' ),
            'manage_options',
            $this->plugin_name . '-settings',
            [ $this, 'display_plugin_setup_page' ],
            $icon_svg_base64,
            75
        );
    }

    /**
     * Renders the main admin settings page by including the partial file.
     */
    public function display_plugin_setup_page() {
        // We pass this object to the view so it can access the render method
        $ui_helper = $this; 
        require_once dirname( dirname( __FILE__ ) ) . '/partials/yakutlogin-admin-panel-display.php';
    }

    /**
     * Enqueues admin-specific stylesheets and scripts.
     */
    public function enqueue_styles_and_scripts( $hook ) {
        // Only load on our plugin's settings page
        if ( 'toplevel_page_' . $this->plugin_name . '-settings' !== $hook ) {
            return;
        }

        // Styles
        wp_enqueue_style( $this->plugin_name . '-admin-panel', plugin_dir_url( __FILE__ ) . '../assets/css/yakutlogin-admin-panel.css', [], $this->version, 'all' );

        // Scripts
        wp_enqueue_script( $this->plugin_name . '-admin-panel', plugin_dir_url( __FILE__ ) . '../assets/js/yakutlogin-admin-panel.js', [ 'jquery', 'wp-util' ], $this->version, true );
        wp_enqueue_script( $this->plugin_name . '-admin-design', plugin_dir_url( __FILE__ ) . '../assets/js/yakutlogin-admin-design.js', [ $this->plugin_name . '-admin-panel' ], $this->version, true );
        
        // Pass data to the main panel script
        wp_localize_script( 
            $this->plugin_name . '-admin-panel', 
            'yakutlogin_admin_ajax', 
            [
                'ajax_url' => admin_url( 'admin-ajax.php' ),
                'nonce'    => wp_create_nonce( 'yakutlogin_admin_nonce' ),
            ] 
        );
    }
    
    /**
     * Renders a single setting field based on its type.
     * This is the crucial method that was missing.
     */
    public function render_setting_field( $id, $type, $options, $placeholder = '' ) {
        $value = $options[$id] ?? '';

        switch ($type) {
            case 'checkbox':
                echo '<label class="switch">';
                echo '<input type="checkbox" name="' . esc_attr($id) . '" value="1" ' . checked(1, $value, false) . '>';
                echo '<span class="slider"></span>';
                echo '</label>';
                break;

            case 'text':
            case 'password':
                echo '<input type="' . esc_attr($type) . '" class="setting-input regular-text" name="' . esc_attr($id) . '" value="' . esc_attr($value) . '" placeholder="' . esc_attr($placeholder) . '">';
                break;
            
            case 'textarea':
                echo '<textarea class="setting-textarea large-text" rows="5" name="' . esc_attr($id) . '">' . esc_textarea($value) . '</textarea>';
                break;

            case 'editor':
                wp_editor(wp_kses_post($value), esc_attr($id), [
                    'textarea_name' => esc_attr($id),
                    'textarea_rows' => 10,
                    'media_buttons' => false,
                    'tinymce'       => [ 'toolbar1' => 'bold,italic,underline,link,unlink,bullist,numlist,undo,redo' ],
                    'quicktags'     => true,
                ]);
                break;

            case 'select_gateway':
            case 'select_gateway_backup':
                if (!$this->gateway_manager) break;
                $gateways = $this->gateway_manager->get_available_gateways();
                $select_id = ($type === 'select_gateway') ? 'primary-sms-provider-select' : 'backup-sms-provider-select';

                echo '<select name="' . esc_attr($id) . '" class="setting-select" id="' . esc_attr($select_id) . '">';
                echo '<option value="">-- ' . __('غیرفعال', 'yakutlogin') . ' --</option>';
                foreach ($gateways as $gateway_id => $gateway) {
                    echo '<option value="' . esc_attr($gateway_id) . '" ' . selected($gateway_id, $value, false) . '>' . esc_html($gateway->get_name()) . '</option>';
                }
                echo '</select>';
                break;
                
            case 'select_captcha':
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
}