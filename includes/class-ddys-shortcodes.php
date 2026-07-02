<?php
/**
 * Shortcodes.
 *
 * @package DDYS_WordPress_Plugin
 */

defined('ABSPATH') || exit;

class DDYS_WP_Shortcodes {
    private DDYS_WP_API_Client $client;
    private DDYS_WP_Renderer $renderer;
    private DDYS_WP_Settings $settings;

    public function __construct(DDYS_WP_API_Client $client, DDYS_WP_Renderer $renderer, DDYS_WP_Settings $settings) {
        $this->client   = $client;
        $this->renderer = $renderer;
        $this->settings = $settings;
    }

    public function hooks(): void {
        foreach ($this->definitions() as $tag => $definition) {
            add_shortcode($tag, array($this, $definition['method']));
        }
        add_action('wp_enqueue_scripts', array($this->renderer, 'enqueue_assets'));
    }

    public function definitions(): array {
        return array(
            'ddys_movies'       => array('method' => 'movies', 'label' => __('Movies', 'ddys-wordpress-plugin')),
            'ddys_latest'       => array('method' => 'latest', 'label' => __('Latest movies', 'ddys-wordpress-plugin')),
            'ddys_hot'          => array('method' => 'hot', 'label' => __('Hot movies', 'ddys-wordpress-plugin')),
            'ddys_search'       => array('method' => 'search', 'label' => __('Search', 'ddys-wordpress-plugin')),
            'ddys_suggest'      => array('method' => 'suggest', 'label' => __('Suggestions', 'ddys-wordpress-plugin')),
            'ddys_calendar'     => array('method' => 'calendar', 'label' => __('Calendar', 'ddys-wordpress-plugin')),
            'ddys_movie'        => array('method' => 'movie', 'label' => __('Movie detail', 'ddys-wordpress-plugin')),
            'ddys_sources'      => array('method' => 'sources', 'label' => __('Movie sources', 'ddys-wordpress-plugin')),
            'ddys_related'      => array('method' => 'related', 'label' => __('Related movies', 'ddys-wordpress-plugin')),
            'ddys_comments'     => array('method' => 'comments', 'label' => __('Comments', 'ddys-wordpress-plugin')),
            'ddys_collections'  => array('method' => 'collections', 'label' => __('Collections', 'ddys-wordpress-plugin')),
            'ddys_collection'   => array('method' => 'collection', 'label' => __('Collection detail', 'ddys-wordpress-plugin')),
            'ddys_shares'       => array('method' => 'shares', 'label' => __('Shares', 'ddys-wordpress-plugin')),
            'ddys_share'        => array('method' => 'share', 'label' => __('Share detail', 'ddys-wordpress-plugin')),
            'ddys_requests'     => array('method' => 'requests', 'label' => __('Requests', 'ddys-wordpress-plugin')),
            'ddys_activities'   => array('method' => 'activities', 'label' => __('Activities', 'ddys-wordpress-plugin')),
            'ddys_user'         => array('method' => 'user', 'label' => __('User profile', 'ddys-wordpress-plugin')),
            'ddys_types'        => array('method' => 'types', 'label' => __('Types', 'ddys-wordpress-plugin')),
            'ddys_genres'       => array('method' => 'genres', 'label' => __('Genres', 'ddys-wordpress-plugin')),
            'ddys_regions'      => array('method' => 'regions', 'label' => __('Regions', 'ddys-wordpress-plugin')),
            'ddys_request_form' => array('method' => 'request_form', 'label' => __('Request form', 'ddys-wordpress-plugin')),
        );
    }

    public function movies($atts = array()): string {
        $atts = $this->atts($atts, array('type' => '', 'genre' => '', 'region' => '', 'year' => '', 'sort' => 'latest', 'page' => 1, 'per_page' => 24));
        return $this->render_get('/movies', $this->query($atts, array('type', 'genre', 'region', 'year', 'sort', 'page', 'per_page')), $atts);
    }

    public function latest($atts = array()): string {
        $atts = $this->atts($atts, array('type' => '', 'genre' => '', 'region' => '', 'year' => '', 'limit' => 12));
        return $this->render_get('/latest', $this->query($atts, array('type', 'genre', 'region', 'year', 'limit')), $atts);
    }

    public function hot($atts = array()): string {
        $atts = $this->atts($atts, array('limit' => 10, 'type' => '', 'genre' => '', 'region' => ''));
        return $this->render_get('/hot', $this->query($atts, array('limit', 'type', 'genre', 'region')), $atts);
    }

    public function search($atts = array()): string {
        $atts = $this->atts($atts, array('q' => '', 'type' => 'movie', 'page' => 1, 'per_page' => 10, 'show_form' => true));
        $q    = isset($_GET['ddys_q']) ? sanitize_text_field(wp_unslash($_GET['ddys_q'])) : $atts['q'];
        $type = isset($_GET['ddys_type']) ? sanitize_key(wp_unslash($_GET['ddys_type'])) : $atts['type'];
        $html = ddys_wp_normalize_bool_attr($atts['show_form'], true) ? $this->renderer->search_form(array('q' => $q, 'type' => $type)) : '';

        if (!$q) {
            return $this->renderer->wrap($html, $atts);
        }

        $payload = $this->client->get('/search', $this->query(array_merge($atts, array('q' => $q, 'type' => $type)), array('q', 'type', 'page', 'per_page')), $this->cache_options($atts));
        if (is_wp_error($payload)) {
            return $html . $this->renderer->error($payload);
        }

        return $html . $this->renderer->list_items($payload, $atts);
    }

    public function suggest($atts = array()): string {
        $atts = $this->atts($atts, array('q' => '', 'limit' => 8));
        return $this->render_get('/suggest', $this->query($atts, array('q', 'limit')), $atts);
    }

    public function calendar($atts = array()): string {
        $atts    = $this->atts($atts, array('year' => '', 'month' => ''));
        $payload = $this->client->get('/calendar', $this->query($atts, array('year', 'month')), $this->cache_options($atts));
        return is_wp_error($payload) ? $this->renderer->error($payload) : $this->renderer->calendar($payload, $atts);
    }

    public function movie($atts = array()): string {
        $atts = $this->atts($atts, array('slug' => ''));
        if (!$atts['slug']) {
            return $this->renderer->error(__('Missing movie slug.', 'ddys-wordpress-plugin'));
        }
        $payload = $this->client->get('/movies/' . rawurlencode($atts['slug']), array(), $this->cache_options($atts));
        return is_wp_error($payload) ? $this->renderer->error($payload) : $this->renderer->movie_detail($payload, $atts);
    }

    public function sources($atts = array()): string {
        $atts = $this->atts($atts, array('slug' => ''));
        if (!$atts['slug']) {
            return $this->renderer->error(__('Missing movie slug.', 'ddys-wordpress-plugin'));
        }
        $payload = $this->client->get('/movies/' . rawurlencode($atts['slug']) . '/sources', array(), $this->cache_options($atts));
        return is_wp_error($payload) ? $this->renderer->error($payload) : $this->renderer->sources($payload, $atts);
    }

    public function related($atts = array()): string {
        $atts = $this->atts($atts, array('slug' => ''));
        if (!$atts['slug']) {
            return $this->renderer->error(__('Missing movie slug.', 'ddys-wordpress-plugin'));
        }
        return $this->render_get('/movies/' . rawurlencode($atts['slug']) . '/related', array(), $atts);
    }

    public function comments($atts = array()): string {
        $atts = $this->atts($atts, array('slug' => '', 'page' => 1, 'per_page' => 20));
        if (!$atts['slug']) {
            return $this->renderer->error(__('Missing movie slug.', 'ddys-wordpress-plugin'));
        }
        return $this->render_get('/movies/' . rawurlencode($atts['slug']) . '/comments', $this->query($atts, array('page', 'per_page')), $atts);
    }

    public function collections($atts = array()): string {
        $atts = $this->atts($atts, array('page' => 1, 'per_page' => 10));
        return $this->render_get('/collections', $this->query($atts, array('page', 'per_page')), $atts);
    }

    public function collection($atts = array()): string {
        $atts = $this->atts($atts, array('slug' => '', 'page' => 1, 'per_page' => 12));
        if (!$atts['slug']) {
            return $this->renderer->error(__('Missing collection slug.', 'ddys-wordpress-plugin'));
        }
        $payload = $this->client->get('/collections/' . rawurlencode($atts['slug']), $this->query($atts, array('page', 'per_page')), $this->cache_options($atts));
        return is_wp_error($payload) ? $this->renderer->error($payload) : $this->renderer->collection_detail($payload, $atts);
    }

    public function shares($atts = array()): string {
        $atts = $this->atts($atts, array('page' => 1, 'per_page' => 10));
        return $this->render_get('/shares', $this->query($atts, array('page', 'per_page')), $atts);
    }

    public function share($atts = array()): string {
        $atts = $this->atts($atts, array('id' => 0));
        $id   = absint($atts['id']);
        if (!$id) {
            return $this->renderer->error(__('Missing share ID.', 'ddys-wordpress-plugin'));
        }
        $payload = $this->client->get('/shares/' . $id, array(), $this->cache_options($atts));
        return is_wp_error($payload) ? $this->renderer->error($payload) : $this->renderer->share_detail($payload, $atts);
    }

    public function requests($atts = array()): string {
        $atts = $this->atts($atts, array('page' => 1, 'per_page' => 10));
        return $this->render_get('/requests', $this->query($atts, array('page', 'per_page')), $atts);
    }

    public function activities($atts = array()): string {
        $atts = $this->atts($atts, array('type' => '', 'page' => 1, 'per_page' => 10));
        return $this->render_get('/activities', $this->query($atts, array('type', 'page', 'per_page')), $atts);
    }

    public function user($atts = array()): string {
        $atts = $this->atts($atts, array('username' => ''));
        if (!$atts['username']) {
            return $this->renderer->error(__('Missing username.', 'ddys-wordpress-plugin'));
        }
        return $this->render_get('/user/' . rawurlencode($atts['username']), array(), $atts);
    }

    public function types($atts = array()): string {
        return $this->dictionary('/types', $atts);
    }

    public function genres($atts = array()): string {
        return $this->dictionary('/genres', $atts);
    }

    public function regions($atts = array()): string {
        return $this->dictionary('/regions', $atts);
    }

    public function request_form($atts = array()): string {
        if (!$this->settings->get('enable_auth_features', false) || !$this->settings->get('enable_request_form', false)) {
            return $this->renderer->wrap('<div class="ddys-wp-empty">' . esc_html__('DDYS request form is disabled.', 'ddys-wordpress-plugin') . '</div>');
        }

        $html = '';
        if (isset($_GET['ddys_request_status'])) {
            $status = sanitize_key(wp_unslash($_GET['ddys_request_status']));
            $messages = array(
                'ok'            => __('Request submitted.', 'ddys-wordpress-plugin'),
                'failed'        => __('Request submission failed.', 'ddys-wordpress-plugin'),
                'rate_limited'  => __('Please wait before submitting again.', 'ddys-wordpress-plugin'),
                'missing_title' => __('Please enter a title.', 'ddys-wordpress-plugin'),
                'invalid_nonce' => __('Request verification failed.', 'ddys-wordpress-plugin'),
            );
            if (isset($messages[$status])) {
                $html .= '<div class="ddys-wp-' . ('ok' === $status ? 'empty' : 'error') . '">' . esc_html($messages[$status]) . '</div>';
            }
        }

        $html .= '<form class="ddys-wp-request-form" method="post">';
        $html .= wp_nonce_field('ddys_wp_request_form', 'ddys_wp_nonce', true, false);
        $html .= '<label>' . esc_html__('Title', 'ddys-wordpress-plugin') . '<input type="text" name="ddys_title" required maxlength="255"></label>';
        $html .= '<label>' . esc_html__('Year', 'ddys-wordpress-plugin') . '<input type="number" name="ddys_year" min="1900" max="2099"></label>';
        $html .= '<label>' . esc_html__('Type', 'ddys-wordpress-plugin') . '<select name="ddys_type"><option value=""></option><option value="movie">Movie</option><option value="series">Series</option><option value="variety">Variety</option><option value="anime">Anime</option></select></label>';
        $html .= '<label>' . esc_html__('Description', 'ddys-wordpress-plugin') . '<textarea name="ddys_description" maxlength="1000"></textarea></label>';
        $html .= '<button type="submit" name="ddys_request_submit" value="1">' . esc_html__('Submit request', 'ddys-wordpress-plugin') . '</button>';
        $html .= '</form>';

        return $this->renderer->wrap($html);
    }

    private function dictionary(string $path, $atts): string {
        $atts    = $this->atts($atts, array());
        $payload = $this->client->get($path, array(), $this->cache_options($atts));
        return is_wp_error($payload) ? $this->renderer->error($payload) : $this->renderer->dictionaries($payload, $atts);
    }

    private function render_get(string $path, array $params, array $atts): string {
        $payload = $this->client->get($path, $params, $this->cache_options($atts));
        return is_wp_error($payload) ? $this->renderer->error($payload) : $this->renderer->list_items($payload, $atts);
    }

    private function atts($atts, array $defaults): array {
        $defaults = array_merge(
            array(
                'layout'      => $this->settings->get('layout', 'grid'),
                'theme'       => $this->settings->get('theme', 'auto'),
                'columns'     => $this->settings->get('columns', 4),
                'target'      => $this->settings->get('target', '_blank'),
                'show_poster' => true,
                'show_rating' => true,
                'cache_ttl'   => '',
            ),
            $defaults
        );

        $atts = shortcode_atts($defaults, is_array($atts) ? $atts : array(), 'ddys');

        foreach ($atts as $key => $value) {
            if (is_string($value)) {
                $atts[$key] = sanitize_text_field($value);
            }
        }

        $atts['columns'] = ddys_wp_int_range($atts['columns'], $this->settings->get('columns', 4), 1, 6);
        return $atts;
    }

    private function query(array $atts, array $keys): array {
        $query = array();
        foreach ($keys as $key) {
            if (!array_key_exists($key, $atts) || '' === $atts[$key]) {
                continue;
            }
            if (in_array($key, array('page', 'per_page', 'limit', 'year', 'month'), true)) {
                $query[$key] = absint($atts[$key]);
            } else {
                $query[$key] = sanitize_text_field($atts[$key]);
            }
        }

        return $query;
    }

    private function cache_options(array $atts): array {
        if (isset($atts['cache_ttl']) && '' !== $atts['cache_ttl']) {
            return array('cache_ttl' => absint($atts['cache_ttl']));
        }
        return array();
    }
}
