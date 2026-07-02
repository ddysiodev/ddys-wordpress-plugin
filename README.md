# DDYS WordPress Plugin

[English](README.md) | [简体中文](README.zh-CN.md)

Official WordPress plugin for the [DDYS](https://ddys.io/) Open API.

It gives WordPress site owners shortcodes, Gutenberg blocks, caching, diagnostics, and a configurable API base URL for the official DDYS API or a self-hosted Worker Proxy.

## Features

- Admin settings page.
- API Base URL for `https://ddys.io/api/v1` or your own `ddys-worker-proxy`.
- API connection test.
- WordPress transient caching.
- Cache management screen.
- Diagnostics screen.
- Shortcode generator.
- Responsive frontend CSS.
- Lightweight Gutenberg blocks without npm build tooling.
- Optional authenticated request form, disabled by default.
- DDYS icons and WordPress.org asset icons.

## Shortcodes

```text
[ddys_movies type="movie" per_page="24"]
[ddys_latest type="movie" limit="12" layout="grid"]
[ddys_hot limit="10" layout="list"]
[ddys_search]
[ddys_suggest q="interstellar"]
[ddys_calendar year="2026" month="7"]
[ddys_movie slug="interstellar"]
[ddys_sources slug="interstellar"]
[ddys_related slug="interstellar"]
[ddys_comments slug="interstellar"]
[ddys_collections per_page="10"]
[ddys_collection slug="best-sci-fi" per_page="12"]
[ddys_shares per_page="10"]
[ddys_share id="1081"]
[ddys_requests per_page="10"]
[ddys_activities type="share" per_page="10"]
[ddys_user username="diduan"]
[ddys_types]
[ddys_genres]
[ddys_regions]
[ddys_request_form]
```

## Blocks

The plugin registers these lightweight dynamic blocks:

- DDYS Latest
- DDYS Hot
- DDYS Search
- DDYS Calendar
- DDYS Movie
- DDYS Collection

## Requirements

- WordPress 6.0+
- Tested up to WordPress 7.0
- PHP 7.4+

## Development Checks

This repository does not require npm for plugin usage. The included Node script checks the package structure and common plugin safety rules:

```bash
node tools/check.mjs
```

## Distribution

This plugin is not published to npm. Use GitHub releases or the WordPress plugin ZIP flow.

## License

GPL-2.0-or-later
