<?php
/**
 * Settings management.
 *
 * @package DDYS_WordPress_Plugin
 */

defined('ABSPATH') || exit;

class DDYS_WP_Settings {
    public function hooks(): void {
        add_action('admin_init', array($this, 'register'));
    }

    public function register(): void {
        register_setting(
            'ddys_wp_settings',
            DDYS_WP_OPTION,
            array(
                'type'              => 'array',
                'sanitize_callback' => array($this, 'sanitize'),
                'default'           => self::defaults(),
            )
        );
    }

    public static function defaults(): array {
        return array(
            'api_base_url'                 => 'https://ddys.io/api/v1',
            'site_base_url'                => 'https://ddys.io',
            'timeout'                      => 12,
            'default_cache_ttl'            => 300,
            'dictionary_cache_ttl'         => 86400,
            'fresh_cache_ttl'              => 300,
            'list_cache_ttl'               => 600,
            'detail_cache_ttl'             => 1800,
            'community_cache_ttl'          => 120,
            'theme'                        => 'auto',
            'layout'                       => 'grid',
            'columns'                      => 4,
            'target'                       => '_blank',
            'show_source_link'             => true,
            'enable_styles'                => true,
            'enable_blocks'                => true,
            'enable_auth_features'         => false,
            'enable_request_form'          => false,
            'api_key'                      => '',
            'debug'                        => false,
        );
    }

    public function get_all(): array {
        $options = get_option(DDYS_WP_OPTION, array());
        return wp_parse_args(is_array($options) ? $options : array(), self::defaults());
    }

    public function get(string $key, $default = null) {
        $options = $this->get_all();
        return array_key_exists($key, $options) ? $options[$key] : $default;
    }

    public function sanitize($input): array {
        $input    = is_array($input) ? $input : array();
        $defaults = self::defaults();

        return array(
            'api_base_url'                 => ddys_wp_normalize_base_url(ddys_wp_get_array_value($input, 'api_base_url', ''), $defaults['api_base_url']),
            'site_base_url'                => ddys_wp_normalize_base_url(ddys_wp_get_array_value($input, 'site_base_url', ''), $defaults['site_base_url']),
            'timeout'                      => ddys_wp_int_range(ddys_wp_get_array_value($input, 'timeout', 12), 12, 1, 30),
            'default_cache_ttl'            => ddys_wp_int_range(ddys_wp_get_array_value($input, 'default_cache_ttl', 300), 300, 0, 604800),
            'dictionary_cache_ttl'         => ddys_wp_int_range(ddys_wp_get_array_value($input, 'dictionary_cache_ttl', 86400), 86400, 0, 604800),
            'fresh_cache_ttl'              => ddys_wp_int_range(ddys_wp_get_array_value($input, 'fresh_cache_ttl', 300), 300, 0, 604800),
            'list_cache_ttl'               => ddys_wp_int_range(ddys_wp_get_array_value($input, 'list_cache_ttl', 600), 600, 0, 604800),
            'detail_cache_ttl'             => ddys_wp_int_range(ddys_wp_get_array_value($input, 'detail_cache_ttl', 1800), 1800, 0, 604800),
            'community_cache_ttl'          => ddys_wp_int_range(ddys_wp_get_array_value($input, 'community_cache_ttl', 120), 120, 0, 604800),
            'theme'                        => ddys_wp_choice(ddys_wp_get_array_value($input, 'theme', 'auto'), array('auto', 'light', 'dark'), 'auto'),
            'layout'                       => ddys_wp_choice(ddys_wp_get_array_value($input, 'layout', 'grid'), array('grid', 'list', 'compact'), 'grid'),
            'columns'                      => ddys_wp_int_range(ddys_wp_get_array_value($input, 'columns', 4), 4, 1, 6),
            'target'                       => in_array(ddys_wp_get_array_value($input, 'target', '_blank'), array('_blank', '_self'), true) ? ddys_wp_get_array_value($input, 'target', '_blank') : '_blank',
            'show_source_link'             => !empty($input['show_source_link']),
            'enable_styles'                => !empty($input['enable_styles']),
            'enable_blocks'                => !empty($input['enable_blocks']),
            'enable_auth_features'         => !empty($input['enable_auth_features']),
            'enable_request_form'          => !empty($input['enable_request_form']),
            'api_key'                      => sanitize_text_field(wp_unslash((string) ddys_wp_get_array_value($input, 'api_key', ''))),
            'debug'                        => !empty($input['debug']),
        );
    }
}
