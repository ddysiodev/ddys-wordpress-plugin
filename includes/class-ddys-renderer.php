<?php
/**
 * Frontend renderer.
 *
 * @package DDYS_WordPress_Plugin
 */

defined('ABSPATH') || exit;

class DDYS_WP_Renderer {
    private DDYS_WP_Settings $settings;

    public function __construct(DDYS_WP_Settings $settings) {
        $this->settings = $settings;
    }

    public function enqueue_assets(): void {
        if (!$this->settings->get('enable_styles', true)) {
            return;
        }

        wp_enqueue_style(
            'ddys-wp-frontend',
            DDYS_WP_URL . 'assets/css/frontend.css',
            array(),
            DDYS_WP_VERSION
        );
    }

    public function wrap(string $html, array $args = array()): string {
        $theme   = ddys_wp_choice(ddys_wp_get_array_value($args, 'theme', $this->settings->get('theme', 'auto')), array('auto', 'light', 'dark'), 'auto');
        $layout  = ddys_wp_choice(ddys_wp_get_array_value($args, 'layout', $this->settings->get('layout', 'grid')), array('grid', 'list', 'compact'), 'grid');
        $classes = array('ddys-wp', 'ddys-wp-theme-' . $theme, 'ddys-wp-layout-' . $layout);

        if (!empty($args['class'])) {
            $classes[] = sanitize_html_class($args['class']);
        }

        return sprintf('<div class="%s">%s</div>', esc_attr(implode(' ', $classes)), $html);
    }

    public function error($error): string {
        $message = is_wp_error($error) ? $error->get_error_message() : (string) $error;
        return $this->wrap('<div class="ddys-wp-error">' . esc_html($message) . '</div>', array('class' => 'notice'));
    }

    public function empty_state(string $message = ''): string {
        $message = $message ?: __('No DDYS items found.', 'ddys-wordpress-plugin');
        return '<div class="ddys-wp-empty">' . esc_html($message) . '</div>';
    }

    public function list_items($payload, array $args = array()): string {
        $data = ddys_wp_get_payload_data($payload);
        if (!is_array($data) || empty($data)) {
            return $this->wrap($this->empty_state(), $args);
        }

        $items = $this->looks_like_single_item($data) ? array($data) : $data;
        $html  = '<div class="ddys-wp-items">';

        foreach ($items as $item) {
            if (is_array($item)) {
                $html .= $this->card($item, $args);
            }
        }

        $html .= '</div>';
        $html .= $this->pagination_meta(ddys_wp_get_payload_meta($payload));
        $html .= $this->source_link();

        return $this->wrap($html, $args);
    }

    public function movie_detail($payload, array $args = array()): string {
        $data = ddys_wp_get_payload_data($payload);
        if (!is_array($data)) {
            return $this->wrap($this->empty_state(), $args);
        }

        $html  = '<article class="ddys-wp-detail">';
        $html .= $this->card($data, array_merge($args, array('detail' => true)));

        $intro = ddys_wp_get_array_value($data, 'description', ddys_wp_get_array_value($data, 'intro', ''));
        if ($intro) {
            $html .= '<div class="ddys-wp-description">' . wp_kses_post(wpautop($intro)) . '</div>';
        }

        $html .= '</article>';
        $html .= $this->source_link(ddys_wp_get_array_value($data, 'url', ''));

        return $this->wrap($html, array_merge($args, array('class' => 'detail-wrap')));
    }

    public function sources($payload, array $args = array()): string {
        $data = ddys_wp_get_payload_data($payload);
        if (!is_array($data) || empty($data)) {
            return $this->wrap($this->empty_state(__('No sources found.', 'ddys-wordpress-plugin')), $args);
        }

        $groups = $this->normalize_source_groups($data);
        $html   = '<div class="ddys-wp-sources">';

        foreach ($groups as $name => $resources) {
            $html     .= '<section class="ddys-wp-source-group"><h3>' . esc_html($name) . '</h3>';
            $html     .= '<ul>';

            if (is_array($resources)) {
                foreach ($resources as $resource) {
                    if (!is_array($resource)) {
                        continue;
                    }
                    $title = ddys_wp_get_array_value($resource, 'title', ddys_wp_get_array_value($resource, 'name', ddys_wp_get_array_value($resource, 'download_type', __('Resource', 'ddys-wordpress-plugin'))));
                    $url   = ddys_wp_get_array_value($resource, 'url', ddys_wp_get_array_value($resource, 'link', ''));
                    $meta  = array_filter(array(ddys_wp_get_array_value($resource, 'quality', ''), ddys_wp_get_array_value($resource, 'format', ''), ddys_wp_get_array_value($resource, 'size', '')));
                    $html .= '<li>' . $this->resource_links((string) $title, (string) $url);
                    if (!empty($meta)) {
                        $html .= ' <span class="ddys-wp-card-meta">' . esc_html(implode(' · ', array_map('strval', $meta))) . '</span>';
                    }
                    $html .= '</li>';
                }
            }

            $html .= '</ul></section>';
        }

        $html .= '</div>';
        return $this->wrap($html, $args);
    }

    public function collection_detail($payload, array $args = array()): string {
        $data = ddys_wp_get_payload_data($payload);
        if (!is_array($data)) {
            return $this->wrap($this->empty_state(), $args);
        }

        $movies = isset($data['movies']) && is_array($data['movies']) ? $data['movies'] : array();
        $html   = '<article class="ddys-wp-detail">';
        $html  .= '<h2>' . esc_html(ddys_wp_get_array_value($data, 'title', __('Collection', 'ddys-wordpress-plugin'))) . '</h2>';
        if (!empty($data['description'])) {
            $html .= '<div class="ddys-wp-description">' . wp_kses_post(wpautop($data['description'])) . '</div>';
        }
        $html .= '</article>';

        if (!empty($movies)) {
            $html .= '<div class="ddys-wp-items">';
            foreach ($movies as $movie) {
                if (is_array($movie)) {
                    $html .= $this->card($movie, $args);
                }
            }
            $html .= '</div>';
        } else {
            $html .= $this->empty_state(__('No movies found in this collection.', 'ddys-wordpress-plugin'));
        }

        $html .= $this->pagination_meta(ddys_wp_get_payload_meta($payload));
        $html .= $this->source_link(ddys_wp_get_array_value($data, 'url', ''));

        return $this->wrap($html, array_merge($args, array('class' => 'collection-detail')));
    }

    public function share_detail($payload, array $args = array()): string {
        $data = ddys_wp_get_payload_data($payload);
        if (!is_array($data)) {
            return $this->wrap($this->empty_state(), $args);
        }

        $html  = '<article class="ddys-wp-detail">';
        $html .= '<h2>' . esc_html(ddys_wp_get_array_value($data, 'title', __('Share', 'ddys-wordpress-plugin'))) . '</h2>';
        $meta  = array_filter(array(ddys_wp_get_array_value($data, 'resource_type', ''), ddys_wp_get_array_value($data, 'quality', ''), ddys_wp_get_array_value($data, 'username', '')));
        if (!empty($meta)) {
            $html .= '<div class="ddys-wp-card-meta">' . esc_html(implode(' · ', array_map('strval', $meta))) . '</div>';
        }
        if (!empty($data['note'])) {
            $html .= '<div class="ddys-wp-description">' . wp_kses_post(wpautop($data['note'])) . '</div>';
        }

        if (!empty($data['resources']) && is_array($data['resources'])) {
            $html .= '<h3>' . esc_html__('Resources', 'ddys-wordpress-plugin') . '</h3><ul class="ddys-wp-resource-list">';
            foreach ($data['resources'] as $resource) {
                if (!is_array($resource)) {
                    continue;
                }
                $title = ddys_wp_get_array_value($resource, 'type', __('Resource', 'ddys-wordpress-plugin'));
                $url   = ddys_wp_get_array_value($resource, 'url', '');
                $html .= '<li>' . $this->resource_links((string) $title, (string) $url) . '</li>';
            }
            $html .= '</ul>';
        }

        $html .= '</article>';
        $html .= $this->source_link(ddys_wp_get_array_value($data, 'url', ''));

        return $this->wrap($html, array_merge($args, array('class' => 'share-detail')));
    }

    public function calendar($payload, array $args = array()): string {
        $data = ddys_wp_get_payload_data($payload);
        if (!is_array($data) || empty($data)) {
            return $this->wrap($this->empty_state(__('No calendar data found.', 'ddys-wordpress-plugin')), $args);
        }

        $days = $this->extract_calendar_days($data);
        if (empty($days)) {
            return $this->wrap($this->empty_state(__('No calendar data found.', 'ddys-wordpress-plugin')), $args);
        }

        $html = '<div class="ddys-wp-calendar">';
        foreach ($days as $day => $items) {
            $html .= '<section class="ddys-wp-calendar-day"><h3>' . esc_html($day) . '</h3><div class="ddys-wp-items">';
            foreach ($items as $item) {
                if (is_array($item)) {
                    $html .= $this->card($item, $args);
                }
            }
            $html .= '</div></section>';
        }
        $html .= '</div>';

        return $this->wrap($html, array_merge($args, array('class' => 'calendar-wrap')));
    }

    public function dictionaries($payload, array $args = array()): string {
        $data = ddys_wp_get_payload_data($payload);
        if (!is_array($data) || empty($data)) {
            return $this->wrap($this->empty_state(), $args);
        }

        $html = '<div class="ddys-wp-taxonomy-list">';
        foreach ($data as $item) {
            if (!is_array($item)) {
                continue;
            }
            $name = ddys_wp_get_array_value($item, 'name', '');
            $code = ddys_wp_get_array_value($item, 'code', '');
            $html .= '<span class="ddys-wp-pill"><span>' . esc_html($name) . '</span>';
            if ($code) {
                $html .= '<code>' . esc_html($code) . '</code>';
            }
            $html .= '</span>';
        }
        $html .= '</div>';

        return $this->wrap($html, $args);
    }

    public function search_form(array $atts = array()): string {
        $query_name = 'ddys_q';
        $type_name  = 'ddys_type';
        $q          = isset($_GET[$query_name]) ? sanitize_text_field(wp_unslash($_GET[$query_name])) : ddys_wp_get_array_value($atts, 'q', '');
        $type       = isset($_GET[$type_name]) ? sanitize_key(wp_unslash($_GET[$type_name])) : ddys_wp_get_array_value($atts, 'type', 'movie');

        $html  = '<form class="ddys-wp-search-form" method="get">';
        $html .= '<label><span class="screen-reader-text">' . esc_html__('Search DDYS', 'ddys-wordpress-plugin') . '</span>';
        $html .= '<input type="search" name="' . esc_attr($query_name) . '" value="' . esc_attr($q) . '" placeholder="' . esc_attr__('Search movies, shares, or requests', 'ddys-wordpress-plugin') . '"></label>';
        $html .= '<select name="' . esc_attr($type_name) . '">';
        foreach (array('movie', 'share', 'request') as $option) {
            $html .= '<option value="' . esc_attr($option) . '"' . selected($type, $option, false) . '>' . esc_html(ucfirst($option)) . '</option>';
        }
        $html .= '</select>';
        $html .= '<button type="submit">' . esc_html__('Search', 'ddys-wordpress-plugin') . '</button>';
        $html .= '</form>';

        return $html;
    }

    private function card(array $item, array $args = array()): string {
        $site_base = untrailingslashit($this->settings->get('site_base_url', 'https://ddys.io'));
        $title     = ddys_wp_get_array_value($item, 'title', ddys_wp_get_array_value($item, 'name', ddys_wp_get_array_value($item, 'username', __('Untitled', 'ddys-wordpress-plugin'))));
        $url       = ddys_wp_get_array_value($item, 'url', '');
        $poster    = ddys_wp_get_array_value($item, 'poster', ddys_wp_get_array_value($item, 'avatar', ''));
        $rating    = ddys_wp_get_array_value($item, 'rating', '');
        $year      = ddys_wp_get_array_value($item, 'year', '');
        $type      = ddys_wp_get_array_value($item, 'type', ddys_wp_get_array_value($item, 'type_code', ''));
        $target    = ddys_wp_get_array_value($args, 'target', $this->settings->get('target', '_blank'));
        $show_poster = ddys_wp_normalize_bool_attr(ddys_wp_get_array_value($args, 'show_poster', true), true);
        $show_rating = ddys_wp_normalize_bool_attr(ddys_wp_get_array_value($args, 'show_rating', true), true);
        $href      = $url ? $this->absolute_site_url($site_base, $url) : '';

        $html = '<article class="ddys-wp-card">';
        if ($show_poster && $poster) {
            $html .= '<div class="ddys-wp-card-poster"><img src="' . esc_url($poster) . '" alt="' . esc_attr($title) . '" loading="lazy"></div>';
        }
        $html .= '<div class="ddys-wp-card-body">';
        $html .= '<h3 class="ddys-wp-card-title">';
        if ($href) {
            $html .= '<a href="' . esc_url($href) . '" target="' . esc_attr($target) . '" rel="noopener">' . esc_html($title) . '</a>';
        } else {
            $html .= esc_html($title);
        }
        $html .= '</h3>';
        $meta = array_filter(array($year, $type, $show_rating ? $rating : ''));
        if (!empty($meta)) {
            $html .= '<div class="ddys-wp-card-meta">' . esc_html(implode(' · ', array_map('strval', $meta))) . '</div>';
        }
        $summary = ddys_wp_get_array_value($item, 'description', ddys_wp_get_array_value($item, 'content', ''));
        if ($summary) {
            $html .= '<div class="ddys-wp-card-summary">' . esc_html(wp_trim_words(wp_strip_all_tags($summary), 28)) . '</div>';
        }
        $html .= '</div></article>';

        return $html;
    }

    private function normalize_source_groups(array $data): array {
        if (isset($data['online']) || isset($data['download'])) {
            return array_filter(
                array(
                    __('Online', 'ddys-wordpress-plugin')   => isset($data['online']) && is_array($data['online']) ? $data['online'] : array(),
                    __('Download', 'ddys-wordpress-plugin') => isset($data['download']) && is_array($data['download']) ? $data['download'] : array(),
                )
            );
        }

        $groups = array();
        foreach ($data as $key => $value) {
            if (is_array($value) && isset($value['resources']) && is_array($value['resources'])) {
                $groups[(string) ddys_wp_get_array_value($value, 'name', $key)] = $value['resources'];
            } elseif (is_array($value)) {
                $groups[(string) $key] = $value;
            }
        }

        return $groups;
    }

    private function resource_links(string $title, string $url): string {
        if (!$url) {
            return esc_html($title);
        }

        $parts = array_filter(explode('#', $url));
        $links = array();
        foreach ($parts as $index => $part) {
            $label = $title;
            $href  = $part;
            if (false !== strpos($part, '$')) {
                list($label, $href) = array_pad(explode('$', $part, 2), 2, '');
            } elseif (count($parts) > 1) {
                $label = $title . ' ' . ($index + 1);
            }

            if ($href && preg_match('#^https?://#i', $href)) {
                $links[] = '<a href="' . esc_url($href) . '" target="' . esc_attr($this->settings->get('target', '_blank')) . '" rel="noopener">' . esc_html($label ?: $title) . '</a>';
            }
        }

        if (empty($links)) {
            return esc_html($title);
        }

        return implode(' ', $links);
    }

    private function pagination_meta(array $meta): string {
        if (empty($meta['total'])) {
            return '';
        }

        $page = isset($meta['page']) ? absint($meta['page']) : 1;
        $total = absint($meta['total']);

        return '<div class="ddys-wp-meta">' . esc_html(sprintf(__('Page %1$d · %2$d total items', 'ddys-wordpress-plugin'), $page, $total)) . '</div>';
    }

    private function source_link(string $path = ''): string {
        if (!$this->settings->get('show_source_link', true)) {
            return '';
        }

        $href = $this->absolute_site_url(untrailingslashit($this->settings->get('site_base_url', 'https://ddys.io')), $path ?: '/');
        return '<div class="ddys-wp-source-link"><a href="' . esc_url($href) . '" target="_blank" rel="noopener">' . esc_html__('View on DDYS', 'ddys-wordpress-plugin') . '</a></div>';
    }

    private function absolute_site_url(string $site_base, string $url): string {
        if (preg_match('#^https?://#i', $url)) {
            return $url;
        }

        return $site_base . '/' . ltrim($url, '/');
    }

    private function looks_like_single_item(array $data): bool {
        return isset($data['id']) || isset($data['slug']) || isset($data['title']) || isset($data['username']);
    }

    private function extract_calendar_days(array $data): array {
        if (isset($data['days']) && is_array($data['days'])) {
            return $data['days'];
        }

        $days = array();
        foreach ($data as $key => $value) {
            if (is_array($value) && preg_match('/^\d{4}-\d{2}-\d{2}$|^\d{1,2}$/', (string) $key)) {
                $days[(string) $key] = $value;
            }
        }

        return $days;
    }
}
