<?php
/**
 * DDYS Open API client.
 *
 * @package DDYS_WordPress_Plugin
 */

defined('ABSPATH') || exit;

class DDYS_WP_API_Client {
    private DDYS_WP_Settings $settings;
    private DDYS_WP_Cache $cache;

    public function __construct(DDYS_WP_Settings $settings, DDYS_WP_Cache $cache) {
        $this->settings = $settings;
        $this->cache    = $cache;
    }

    public function get(string $path, array $params = array(), array $options = array()) {
        return $this->request('GET', $path, $params, null, $options);
    }

    public function post(string $path, array $body = array(), array $options = array()) {
        return $this->request('POST', $path, array(), $body, $options);
    }

    public function delete(string $path, array $options = array()) {
        return $this->request('DELETE', $path, array(), null, $options);
    }

    public function request(string $method, string $path, array $params = array(), ?array $body = null, array $options = array()) {
        $method   = strtoupper($method);
        $settings = $this->settings->get_all();
        $base_url = untrailingslashit($settings['api_base_url']);
        $path     = '/' . ltrim($path, '/');
        $params   = ddys_wp_build_query($params);
        $url      = add_query_arg($params, $base_url . $path);
        $ttl      = isset($options['cache_ttl']) ? absint($options['cache_ttl']) : $this->ttl_for_path($path, $settings);
        $use_cache = 'GET' === $method && empty($options['no_cache']);
        $cache_key = $this->cache->key($method, $base_url, $path, $params);

        if ($use_cache) {
            $cached = $this->cache->get($cache_key);
            if (false !== $cached) {
                return $cached;
            }
        }

        $headers = array(
            'Accept'     => 'application/json',
            'User-Agent' => 'ddys-wordpress-plugin/' . DDYS_WP_VERSION . '; ' . home_url('/'),
        );

        if (!empty($options['auth']) && !empty($settings['api_key'])) {
            $headers['Authorization'] = 'Bearer ' . $settings['api_key'];
        }

        $args = array(
            'method'      => $method,
            'timeout'     => absint($settings['timeout']),
            'redirection' => 3,
            'headers'     => $headers,
        );

        if (null !== $body) {
            $args['headers']['Content-Type'] = 'application/json';
            $args['body']                    = wp_json_encode($body);
        }

        $response = wp_remote_request(esc_url_raw($url), $args);
        if (is_wp_error($response)) {
            return $response;
        }

        $status = wp_remote_retrieve_response_code($response);
        $raw    = wp_remote_retrieve_body($response);
        $json   = json_decode($raw, true);

        if (!is_array($json)) {
            return new WP_Error('ddys_wp_invalid_json', __('DDYS API returned invalid JSON.', 'ddys-wordpress-plugin'), array('status' => $status));
        }

        if ($status < 200 || $status >= 300 || !ddys_wp_is_success_response($json)) {
            $message = isset($json['message']) ? sanitize_text_field((string) $json['message']) : sprintf(__('DDYS API request failed with HTTP %d.', 'ddys-wordpress-plugin'), $status);
            return new WP_Error('ddys_wp_api_error', $message, array('status' => $status, 'payload' => $json));
        }

        if ($use_cache && $ttl > 0) {
            $this->cache->set($cache_key, $json, $ttl);
        }

        return $json;
    }

    private function ttl_for_path(string $path, array $settings): int {
        if (preg_match('#^/(types|genres|regions|calendar)$#', $path)) {
            return absint($settings['dictionary_cache_ttl']);
        }

        if (preg_match('#^/(latest|hot)$#', $path)) {
            return absint($settings['fresh_cache_ttl']);
        }

        if (preg_match('#^/(movies/[^/]+|movies/[^/]+/sources|movies/[^/]+/related|collections/[^/]+|shares/[0-9]+)$#', $path)) {
            return absint($settings['detail_cache_ttl']);
        }

        if (preg_match('#^/(movies/[^/]+/comments|suggest|shares|requests|activities|user/)#', $path)) {
            return absint($settings['community_cache_ttl']);
        }

        if (preg_match('#^/(movies|search|collections)#', $path)) {
            return absint($settings['list_cache_ttl']);
        }

        return absint($settings['default_cache_ttl']);
    }
}
