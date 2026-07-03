<?php
/**
 * Plugin Name:       DDYS WordPress Plugin
 * Plugin URI:        https://github.com/ddysiodev/ddys-wordpress-plugin
 * Description:       Embed DDYS API content with shortcodes, blocks, caching, and a configurable API base URL.
 * Version:           0.1.0
 * Requires at least: 6.0
 * Tested up to:      7.0
 * Requires PHP:      7.4
 * Author:            DDYS
 * Author URI:        https://ddys.io/
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       ddys-wordpress-plugin
 * Domain Path:       /languages
 *
 * @package DDYS_WordPress_Plugin
 */

defined('ABSPATH') || exit;

define('DDYS_WP_VERSION', '0.1.0');
define('DDYS_WP_FILE', __FILE__);
define('DDYS_WP_PATH', plugin_dir_path(__FILE__));
define('DDYS_WP_URL', plugin_dir_url(__FILE__));
define('DDYS_WP_OPTION', 'ddys_wp_options');
define('DDYS_WP_CACHE_INDEX', 'ddys_wp_cache_keys');

require_once DDYS_WP_PATH . 'includes/functions.php';
require_once DDYS_WP_PATH . 'includes/class-ddys-cache.php';
require_once DDYS_WP_PATH . 'includes/class-ddys-settings.php';
require_once DDYS_WP_PATH . 'includes/class-ddys-api-client.php';
require_once DDYS_WP_PATH . 'includes/class-ddys-renderer.php';
require_once DDYS_WP_PATH . 'includes/class-ddys-shortcodes.php';
require_once DDYS_WP_PATH . 'includes/class-ddys-blocks.php';
require_once DDYS_WP_PATH . 'includes/class-ddys-admin.php';
require_once DDYS_WP_PATH . 'includes/class-ddys-plugin.php';

register_activation_hook(__FILE__, array('DDYS_WP_Plugin', 'activate'));
register_deactivation_hook(__FILE__, array('DDYS_WP_Plugin', 'deactivate'));

add_action('plugins_loaded', array('DDYS_WP_Plugin', 'instance'));
