<?php
/**
 * Gutenberg blocks.
 *
 * @package DDYS_WordPress_Plugin
 */

defined('ABSPATH') || exit;

class DDYS_WP_Blocks {
    private DDYS_WP_Settings $settings;

    public function __construct(DDYS_WP_Settings $settings) {
        $this->settings = $settings;
    }

    public function hooks(): void {
        if (!$this->settings->get('enable_blocks', true)) {
            return;
        }

        add_action('init', array($this, 'register'));
    }

    public function register(): void {
        wp_register_script(
            'ddys-wp-blocks',
            DDYS_WP_URL . 'assets/js/blocks.js',
            array('wp-blocks', 'wp-element', 'wp-components', 'wp-i18n', 'wp-server-side-render'),
            DDYS_WP_VERSION,
            true
        );

        $blocks = array(
            'latest'     => '[ddys_latest limit="%d" layout="%s"]',
            'hot'        => '[ddys_hot limit="%d" layout="%s"]',
            'search'     => '[ddys_search]',
            'calendar'   => '[ddys_calendar]',
            'movie'      => '[ddys_movie slug="%s"]',
            'collection' => '[ddys_collection slug="%s"]',
        );

        foreach ($blocks as $name => $template) {
            register_block_type(
                'ddys/' . $name,
                array(
                    'editor_script'   => 'ddys-wp-blocks',
                    'render_callback' => function ($attributes) use ($name, $template) {
                        $limit  = isset($attributes['limit']) ? absint($attributes['limit']) : 12;
                        $layout = isset($attributes['layout']) ? sanitize_key($attributes['layout']) : 'grid';
                        $slug   = isset($attributes['slug']) ? sanitize_text_field($attributes['slug']) : '';

                        if (in_array($name, array('movie', 'collection'), true)) {
                            return do_shortcode(sprintf($template, esc_attr($slug)));
                        }

                        if (in_array($name, array('search', 'calendar'), true)) {
                            return do_shortcode($template);
                        }

                        return do_shortcode(sprintf($template, $limit, esc_attr($layout)));
                    },
                    'attributes'      => array(
                        'limit'  => array('type' => 'number', 'default' => 12),
                        'layout' => array('type' => 'string', 'default' => 'grid'),
                        'slug'   => array('type' => 'string', 'default' => ''),
                    ),
                )
            );
        }
    }
}
