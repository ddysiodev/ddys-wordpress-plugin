<?php
/**
 * Admin screens.
 *
 * @package DDYS_WordPress_Plugin
 */

defined('ABSPATH') || exit;

class DDYS_WP_Admin {
    private DDYS_WP_Settings $settings;
    private DDYS_WP_API_Client $client;
    private DDYS_WP_Cache $cache;
    private DDYS_WP_Shortcodes $shortcodes;

    public function __construct(DDYS_WP_Settings $settings, DDYS_WP_API_Client $client, DDYS_WP_Cache $cache, DDYS_WP_Shortcodes $shortcodes) {
        $this->settings   = $settings;
        $this->client     = $client;
        $this->cache      = $cache;
        $this->shortcodes = $shortcodes;
    }

    public function hooks(): void {
        add_action('admin_menu', array($this, 'menu'));
        add_action('admin_enqueue_scripts', array($this, 'assets'));
        add_action('admin_notices', array($this, 'notices'));
        add_action('admin_post_ddys_wp_flush_cache', array($this, 'flush_cache'));
        add_action('admin_post_ddys_wp_test_api', array($this, 'test_api'));
    }

    public function menu(): void {
        add_menu_page(
            __('DDYS', 'ddys-wordpress-plugin'),
            __('DDYS', 'ddys-wordpress-plugin'),
            'manage_options',
            'ddys-wp',
            array($this, 'settings_page'),
            'dashicons-video-alt3',
            58
        );

        add_submenu_page('ddys-wp', __('Settings', 'ddys-wordpress-plugin'), __('Settings', 'ddys-wordpress-plugin'), 'manage_options', 'ddys-wp', array($this, 'settings_page'));
        add_submenu_page('ddys-wp', __('Shortcode Generator', 'ddys-wordpress-plugin'), __('Shortcodes', 'ddys-wordpress-plugin'), 'manage_options', 'ddys-wp-shortcodes', array($this, 'shortcodes_page'));
        add_submenu_page('ddys-wp', __('Cache', 'ddys-wordpress-plugin'), __('Cache', 'ddys-wordpress-plugin'), 'manage_options', 'ddys-wp-cache', array($this, 'cache_page'));
        add_submenu_page('ddys-wp', __('Diagnostics', 'ddys-wordpress-plugin'), __('Diagnostics', 'ddys-wordpress-plugin'), 'manage_options', 'ddys-wp-diagnostics', array($this, 'diagnostics_page'));
    }

    public function assets(string $hook): void {
        if (false === strpos($hook, 'ddys-wp')) {
            return;
        }

        wp_enqueue_style('ddys-wp-admin', DDYS_WP_URL . 'assets/css/admin.css', array(), DDYS_WP_VERSION);
        wp_enqueue_script('ddys-wp-admin', DDYS_WP_URL . 'assets/js/admin.js', array(), DDYS_WP_VERSION, true);
    }

    public function settings_page(): void {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('You do not have permission to access this page.', 'ddys-wordpress-plugin'));
        }

        $options = $this->settings->get_all();
        ?>
        <div class="wrap ddys-wp-admin">
            <?php $this->header(__('DDYS Settings', 'ddys-wordpress-plugin')); ?>
            <form method="post" action="options.php">
                <?php settings_fields('ddys_wp_settings'); ?>
                <table class="form-table" role="presentation">
                    <?php $this->text_row('api_base_url', __('API Base URL', 'ddys-wordpress-plugin'), $options['api_base_url']); ?>
                    <?php $this->text_row('site_base_url', __('Site Base URL', 'ddys-wordpress-plugin'), $options['site_base_url']); ?>
                    <?php $this->number_row('timeout', __('Request timeout', 'ddys-wordpress-plugin'), $options['timeout'], 1, 30); ?>
                    <?php $this->number_row('default_cache_ttl', __('Default cache TTL', 'ddys-wordpress-plugin'), $options['default_cache_ttl'], 0, 604800); ?>
                    <?php $this->number_row('dictionary_cache_ttl', __('Dictionary cache TTL', 'ddys-wordpress-plugin'), $options['dictionary_cache_ttl'], 0, 604800); ?>
                    <?php $this->number_row('fresh_cache_ttl', __('Latest and hot cache TTL', 'ddys-wordpress-plugin'), $options['fresh_cache_ttl'], 0, 604800); ?>
                    <?php $this->number_row('list_cache_ttl', __('List cache TTL', 'ddys-wordpress-plugin'), $options['list_cache_ttl'], 0, 604800); ?>
                    <?php $this->number_row('detail_cache_ttl', __('Detail cache TTL', 'ddys-wordpress-plugin'), $options['detail_cache_ttl'], 0, 604800); ?>
                    <?php $this->number_row('community_cache_ttl', __('Community cache TTL', 'ddys-wordpress-plugin'), $options['community_cache_ttl'], 0, 604800); ?>
                    <?php $this->select_row('theme', __('Theme', 'ddys-wordpress-plugin'), $options['theme'], array('auto' => 'Auto', 'light' => 'Light', 'dark' => 'Dark')); ?>
                    <?php $this->select_row('layout', __('Default layout', 'ddys-wordpress-plugin'), $options['layout'], array('grid' => 'Grid', 'list' => 'List', 'compact' => 'Compact')); ?>
                    <?php $this->number_row('columns', __('Default columns', 'ddys-wordpress-plugin'), $options['columns'], 1, 6); ?>
                    <?php $this->select_row('target', __('Link target', 'ddys-wordpress-plugin'), $options['target'], array('_blank' => '_blank', '_self' => '_self')); ?>
                    <?php $this->checkbox_row('show_source_link', __('Show source link', 'ddys-wordpress-plugin'), $options['show_source_link']); ?>
                    <?php $this->checkbox_row('enable_styles', __('Load frontend styles', 'ddys-wordpress-plugin'), $options['enable_styles']); ?>
                    <?php $this->checkbox_row('enable_blocks', __('Enable Gutenberg blocks', 'ddys-wordpress-plugin'), $options['enable_blocks']); ?>
                    <?php $this->checkbox_row('enable_auth_features', __('Enable authenticated features', 'ddys-wordpress-plugin'), $options['enable_auth_features']); ?>
                    <?php $this->checkbox_row('enable_request_form', __('Enable request form shortcode', 'ddys-wordpress-plugin'), $options['enable_request_form']); ?>
                    <?php $this->password_row('api_key', __('DDYS API Key', 'ddys-wordpress-plugin'), $options['api_key']); ?>
                    <?php $this->checkbox_row('debug', __('Debug mode', 'ddys-wordpress-plugin'), $options['debug']); ?>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }

    public function notices(): void {
        if (!current_user_can('manage_options')) {
            return;
        }

        if (isset($_GET['ddys_flushed'])) {
            $count = absint($_GET['ddys_flushed']);
            echo '<div class="notice notice-success is-dismissible"><p>' . esc_html(sprintf(__('DDYS cache flushed. Removed %d tracked entries.', 'ddys-wordpress-plugin'), $count)) . '</p></div>';
        }

        if (isset($_GET['ddys_api_test'])) {
            $status = sanitize_key(wp_unslash($_GET['ddys_api_test']));
            if ('ok' === $status) {
                echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('DDYS API connection succeeded.', 'ddys-wordpress-plugin') . '</p></div>';
            } else {
                echo '<div class="notice notice-error is-dismissible"><p>' . esc_html__('DDYS API connection failed. Check API Base URL and network access.', 'ddys-wordpress-plugin') . '</p></div>';
            }
        }
    }

    public function shortcodes_page(): void {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('You do not have permission to access this page.', 'ddys-wordpress-plugin'));
        }

        $definitions = $this->shortcodes->definitions();
        ?>
        <div class="wrap ddys-wp-admin">
            <?php $this->header(__('DDYS Shortcode Generator', 'ddys-wordpress-plugin')); ?>
            <div class="ddys-wp-admin-grid">
                <section class="ddys-wp-panel">
                    <h2><?php esc_html_e('Generate shortcode', 'ddys-wordpress-plugin'); ?></h2>
                    <label>
                        <?php esc_html_e('Shortcode', 'ddys-wordpress-plugin'); ?>
                        <select id="ddys-wp-shortcode-kind">
                            <?php foreach ($definitions as $tag => $definition) : ?>
                                <option value="<?php echo esc_attr($tag); ?>"><?php echo esc_html($tag . ' - ' . $definition['label']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label>slug <input id="ddys-wp-shortcode-slug" type="text" placeholder="interstellar"></label>
                    <label>id <input id="ddys-wp-shortcode-id" type="number" min="1"></label>
                    <label>type <input id="ddys-wp-shortcode-type" type="text" placeholder="movie"></label>
                    <label>limit <input id="ddys-wp-shortcode-limit" type="number" min="1" max="50" value="12"></label>
                    <label>per_page <input id="ddys-wp-shortcode-per-page" type="number" min="1" max="50" value="10"></label>
                    <label>layout
                        <select id="ddys-wp-shortcode-layout">
                            <option value="grid">grid</option>
                            <option value="list">list</option>
                            <option value="compact">compact</option>
                        </select>
                    </label>
                    <button type="button" class="button button-primary" id="ddys-wp-shortcode-build"><?php esc_html_e('Build', 'ddys-wordpress-plugin'); ?></button>
                </section>
                <section class="ddys-wp-panel">
                    <h2><?php esc_html_e('Output', 'ddys-wordpress-plugin'); ?></h2>
                    <textarea id="ddys-wp-shortcode-output" rows="6" readonly>[ddys_latest limit="12"]</textarea>
                    <p><button type="button" class="button" id="ddys-wp-shortcode-copy"><?php esc_html_e('Copy', 'ddys-wordpress-plugin'); ?></button></p>
                    <h3><?php esc_html_e('Common examples', 'ddys-wordpress-plugin'); ?></h3>
                    <pre>[ddys_latest type="movie" limit="12"]
[ddys_hot limit="10"]
[ddys_search]
[ddys_calendar year="2026" month="7"]
[ddys_movie slug="interstellar"]
[ddys_sources slug="interstellar"]
[ddys_collection slug="best-sci-fi" per_page="12"]</pre>
                </section>
            </div>
        </div>
        <?php
    }

    public function cache_page(): void {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('You do not have permission to access this page.', 'ddys-wordpress-plugin'));
        }

        $keys = $this->cache->keys();
        ?>
        <div class="wrap ddys-wp-admin">
            <?php $this->header(__('DDYS Cache', 'ddys-wordpress-plugin')); ?>
            <p><?php echo esc_html(sprintf(__('Tracked cache entries: %d', 'ddys-wordpress-plugin'), count($keys))); ?></p>
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <?php wp_nonce_field('ddys_wp_flush_cache'); ?>
                <input type="hidden" name="action" value="ddys_wp_flush_cache">
                <?php submit_button(__('Flush DDYS cache', 'ddys-wordpress-plugin'), 'delete'); ?>
            </form>
        </div>
        <?php
    }

    public function diagnostics_page(): void {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('You do not have permission to access this page.', 'ddys-wordpress-plugin'));
        }

        ?>
        <div class="wrap ddys-wp-admin">
            <?php $this->header(__('DDYS Diagnostics', 'ddys-wordpress-plugin')); ?>
            <table class="widefat striped">
                <tbody>
                    <tr><th>Plugin</th><td><?php echo esc_html(DDYS_WP_VERSION); ?></td></tr>
                    <tr><th>WordPress</th><td><?php echo esc_html(get_bloginfo('version')); ?></td></tr>
                    <tr><th>PHP</th><td><?php echo esc_html(PHP_VERSION); ?></td></tr>
                    <tr><th>API Base</th><td><?php echo esc_html($this->settings->get('api_base_url')); ?></td></tr>
                    <tr><th>Cache entries</th><td><?php echo esc_html((string) count($this->cache->keys())); ?></td></tr>
                </tbody>
            </table>
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <?php wp_nonce_field('ddys_wp_test_api'); ?>
                <input type="hidden" name="action" value="ddys_wp_test_api">
                <?php submit_button(__('Test DDYS API', 'ddys-wordpress-plugin')); ?>
            </form>
        </div>
        <?php
    }

    public function flush_cache(): void {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('You do not have permission to access this page.', 'ddys-wordpress-plugin'));
        }
        check_admin_referer('ddys_wp_flush_cache');
        $count = $this->cache->flush();
        wp_safe_redirect(add_query_arg(array('page' => 'ddys-wp-cache', 'ddys_flushed' => $count), admin_url('admin.php')));
        exit;
    }

    public function test_api(): void {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('You do not have permission to access this page.', 'ddys-wordpress-plugin'));
        }
        check_admin_referer('ddys_wp_test_api');
        $result = $this->client->get('/types', array(), array('no_cache' => true));
        $status = is_wp_error($result) ? 'failed' : 'ok';
        wp_safe_redirect(add_query_arg(array('page' => 'ddys-wp-diagnostics', 'ddys_api_test' => $status), admin_url('admin.php')));
        exit;
    }

    private function header(string $title): void {
        ?>
        <div class="ddys-wp-admin-header">
            <img src="<?php echo esc_url(DDYS_WP_URL . 'assets/images/icon-32.png'); ?>" alt="" width="32" height="32">
            <h1><?php echo esc_html($title); ?></h1>
        </div>
        <?php
    }

    private function text_row(string $key, string $label, string $value): void {
        echo '<tr><th scope="row"><label for="' . esc_attr($key) . '">' . esc_html($label) . '</label></th><td><input class="regular-text" id="' . esc_attr($key) . '" name="' . esc_attr(DDYS_WP_OPTION . '[' . $key . ']') . '" type="url" value="' . esc_attr($value) . '"></td></tr>';
    }

    private function password_row(string $key, string $label, string $value): void {
        echo '<tr><th scope="row"><label for="' . esc_attr($key) . '">' . esc_html($label) . '</label></th><td><input class="regular-text" id="' . esc_attr($key) . '" name="' . esc_attr(DDYS_WP_OPTION . '[' . $key . ']') . '" type="password" autocomplete="off" value="' . esc_attr($value) . '"></td></tr>';
    }

    private function number_row(string $key, string $label, int $value, int $min, int $max): void {
        echo '<tr><th scope="row"><label for="' . esc_attr($key) . '">' . esc_html($label) . '</label></th><td><input id="' . esc_attr($key) . '" name="' . esc_attr(DDYS_WP_OPTION . '[' . $key . ']') . '" type="number" min="' . esc_attr((string) $min) . '" max="' . esc_attr((string) $max) . '" value="' . esc_attr((string) $value) . '"></td></tr>';
    }

    private function checkbox_row(string $key, string $label, bool $value): void {
        echo '<tr><th scope="row">' . esc_html($label) . '</th><td><label><input name="' . esc_attr(DDYS_WP_OPTION . '[' . $key . ']') . '" type="checkbox" value="1"' . checked($value, true, false) . '> ' . esc_html__('Enabled', 'ddys-wordpress-plugin') . '</label></td></tr>';
    }

    private function select_row(string $key, string $label, string $value, array $choices): void {
        echo '<tr><th scope="row"><label for="' . esc_attr($key) . '">' . esc_html($label) . '</label></th><td><select id="' . esc_attr($key) . '" name="' . esc_attr(DDYS_WP_OPTION . '[' . $key . ']') . '">';
        foreach ($choices as $choice => $choice_label) {
            echo '<option value="' . esc_attr($choice) . '"' . selected($value, $choice, false) . '>' . esc_html($choice_label) . '</option>';
        }
        echo '</select></td></tr>';
    }
}
