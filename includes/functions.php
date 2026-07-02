<?php
/**
 * Shared helpers.
 *
 * @package DDYS_WordPress_Plugin
 */

defined('ABSPATH') || exit;

function ddys_wp_bool($value): bool {
    if (is_bool($value)) {
        return $value;
    }

    return in_array(strtolower((string) $value), array('1', 'true', 'yes', 'on'), true);
}

function ddys_wp_int_range($value, int $default, int $min, int $max): int {
    $number = absint($value);
    if ($number < $min) {
        return $default;
    }
    if ($number > $max) {
        return $max;
    }
    return $number;
}

function ddys_wp_choice($value, array $allowed, string $default): string {
    $value = sanitize_key((string) $value);
    return in_array($value, $allowed, true) ? $value : $default;
}

function ddys_wp_normalize_base_url($url, string $default): string {
    $url = esc_url_raw((string) $url);
    if (!$url || !wp_http_validate_url($url)) {
        return $default;
    }

    $parts = wp_parse_url($url);
    if (empty($parts['scheme']) || 'https' !== strtolower($parts['scheme'])) {
        return $default;
    }

    return untrailingslashit($url);
}

function ddys_wp_normalize_text($value): string {
    return sanitize_text_field(wp_unslash((string) $value));
}

function ddys_wp_normalize_bool_attr($value, bool $default = false): bool {
    if ('' === $value || null === $value) {
        return $default;
    }

    return ddys_wp_bool($value);
}

function ddys_wp_get_array_value(array $array, string $key, $default = null) {
    return array_key_exists($key, $array) ? $array[$key] : $default;
}

function ddys_wp_is_success_response($payload): bool {
    return is_array($payload) && (!isset($payload['success']) || true === (bool) $payload['success']);
}

function ddys_wp_get_payload_data($payload) {
    if (is_array($payload) && array_key_exists('data', $payload)) {
        return $payload['data'];
    }

    return $payload;
}

function ddys_wp_get_payload_meta($payload): array {
    if (is_array($payload) && isset($payload['meta']) && is_array($payload['meta'])) {
        return $payload['meta'];
    }

    return array();
}

function ddys_wp_build_query(array $params): array {
    $output = array();

    foreach ($params as $key => $value) {
        if (null === $value || '' === $value || array() === $value) {
            continue;
        }
        $output[$key] = $value;
    }

    ksort($output);
    return $output;
}

function ddys_wp_public_get_routes(): array {
    return array(
        'movies',
        'latest',
        'hot',
        'search',
        'suggest',
        'calendar',
        'movie',
        'sources',
        'related',
        'comments',
        'collections',
        'collection',
        'shares',
        'share',
        'requests',
        'activities',
        'user',
        'types',
        'genres',
        'regions',
    );
}

function ddys_wp_allowed_types(): array {
    return array('movie', 'series', 'variety', 'anime');
}
