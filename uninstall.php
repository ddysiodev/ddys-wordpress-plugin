<?php
/**
 * Uninstall cleanup.
 *
 * @package DDYS_WordPress_Plugin
 */

defined('WP_UNINSTALL_PLUGIN') || exit;

$keys = get_option('ddys_wp_cache_keys', array());
if (is_array($keys)) {
    foreach (array_keys($keys) as $key) {
        delete_transient($key);
    }
}

delete_option('ddys_wp_cache_keys');
delete_option('ddys_wp_options');
