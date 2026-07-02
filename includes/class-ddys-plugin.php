<?php
/**
 * Main plugin container.
 *
 * @package DDYS_WordPress_Plugin
 */

defined('ABSPATH') || exit;

class DDYS_WP_Plugin {
    private static ?DDYS_WP_Plugin $instance = null;

    private DDYS_WP_Settings $settings;
    private DDYS_WP_Cache $cache;
    private DDYS_WP_API_Client $client;
    private DDYS_WP_Renderer $renderer;
    private DDYS_WP_Shortcodes $shortcodes;
    private DDYS_WP_Blocks $blocks;
    private DDYS_WP_Admin $admin;

    public static function instance(): DDYS_WP_Plugin {
        if (null === self::$instance) {
            self::$instance = new self();
            self::$instance->hooks();
        }

        return self::$instance;
    }

    public static function activate(): void {
        if (!get_option(DDYS_WP_OPTION)) {
            add_option(DDYS_WP_OPTION, DDYS_WP_Settings::defaults(), '', false);
        }
    }

    public static function deactivate(): void {
        // Cache is intentionally kept on deactivation and removed on uninstall.
    }

    private function __construct() {
        $this->settings   = new DDYS_WP_Settings();
        $this->cache      = new DDYS_WP_Cache();
        $this->client     = new DDYS_WP_API_Client($this->settings, $this->cache);
        $this->renderer   = new DDYS_WP_Renderer($this->settings);
        $this->shortcodes = new DDYS_WP_Shortcodes($this->client, $this->renderer, $this->settings);
        $this->blocks     = new DDYS_WP_Blocks($this->settings);
        $this->admin      = new DDYS_WP_Admin($this->settings, $this->client, $this->cache, $this->shortcodes);
    }

    private function hooks(): void {
        load_plugin_textdomain('ddys-wordpress-plugin', false, dirname(plugin_basename(DDYS_WP_FILE)) . '/languages');

        $this->settings->hooks();
        $this->shortcodes->hooks();
        $this->blocks->hooks();

        if (is_admin()) {
            $this->admin->hooks();
        }

        add_action('init', array($this, 'handle_request_form'));
    }

    public function handle_request_form(): void {
        if (empty($_POST['ddys_request_submit'])) {
            return;
        }

        if (!$this->settings->get('enable_auth_features', false) || !$this->settings->get('enable_request_form', false)) {
            return;
        }

        if (!isset($_POST['ddys_wp_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['ddys_wp_nonce'])), 'ddys_wp_request_form')) {
            $this->redirect_request_form('invalid_nonce');
        }

        $ip       = isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR'])) : 'unknown';
        $lock_key = 'ddys_wp_request_form_' . md5($ip);
        if (get_transient($lock_key)) {
            $this->redirect_request_form('rate_limited');
        }

        $title       = isset($_POST['ddys_title']) ? sanitize_text_field(wp_unslash($_POST['ddys_title'])) : '';
        $year        = isset($_POST['ddys_year']) ? absint(wp_unslash($_POST['ddys_year'])) : 0;
        $type        = isset($_POST['ddys_type']) ? sanitize_key(wp_unslash($_POST['ddys_type'])) : '';
        $description = isset($_POST['ddys_description']) ? sanitize_textarea_field(wp_unslash($_POST['ddys_description'])) : '';
        $body        = array('title' => $title);

        if (!$title) {
            $this->redirect_request_form('missing_title');
        }

        if ($year >= 1900 && $year <= 2099) {
            $body['year'] = $year;
        }

        if (in_array($type, ddys_wp_allowed_types(), true)) {
            $body['type'] = $type;
        }

        if ($description) {
            $body['description'] = $description;
        }

        set_transient($lock_key, 1, 30);
        $result = $this->client->post('/requests', $body, array('auth' => true));
        $this->redirect_request_form(is_wp_error($result) ? 'failed' : 'ok');
    }

    private function redirect_request_form(string $status): void {
        $referer = wp_get_referer();
        $url     = $referer ? $referer : home_url('/');
        wp_safe_redirect(add_query_arg('ddys_request_status', sanitize_key($status), $url));
        exit;
    }
}
