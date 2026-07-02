<?php
/**
 * Transient cache.
 *
 * @package DDYS_WordPress_Plugin
 */

defined('ABSPATH') || exit;

class DDYS_WP_Cache {
    public function key(string $method, string $base_url, string $path, array $params = array()): string {
        $normalized = array(
            'method' => strtoupper($method),
            'base'   => untrailingslashit($base_url),
            'path'   => $path,
            'params' => ddys_wp_build_query($params),
        );

        return 'ddys_wp_' . md5(wp_json_encode($normalized));
    }

    public function get(string $key) {
        return get_transient($key);
    }

    public function set(string $key, $value, int $ttl): void {
        if ($ttl <= 0) {
            return;
        }

        set_transient($key, $value, $ttl);
        $this->remember_key($key);
    }

    public function delete(string $key): void {
        delete_transient($key);
        $keys = $this->keys();
        unset($keys[$key]);
        update_option(DDYS_WP_CACHE_INDEX, $keys, false);
    }

    public function flush(): int {
        $keys  = $this->keys();
        $count = 0;

        foreach (array_keys($keys) as $key) {
            delete_transient($key);
            $count++;
        }

        delete_option(DDYS_WP_CACHE_INDEX);
        return $count;
    }

    public function keys(): array {
        $keys = get_option(DDYS_WP_CACHE_INDEX, array());
        return is_array($keys) ? $keys : array();
    }

    private function remember_key(string $key): void {
        $keys         = $this->keys();
        $keys[$key]   = time();
        $max_entries   = 500;
        $overflow      = count($keys) - $max_entries;

        if ($overflow > 0) {
            asort($keys);
            $keys = array_slice($keys, $overflow, null, true);
        }

        update_option(DDYS_WP_CACHE_INDEX, $keys, false);
    }
}
