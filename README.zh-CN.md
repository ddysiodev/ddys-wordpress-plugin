# DDYS WordPress Plugin

[English](README.md) | [简体中文](README.zh-CN.md)

[低端影视](https://ddys.io/) Open API 的官方 WordPress 插件。

站长安装后，可以在 WordPress 文章、页面、区块编辑器里直接嵌入 DDYS 内容，并且支持缓存、诊断、短代码生成器，以及自定义 API Base URL。

## 功能

- 后台 DDYS 设置页。
- API Base URL，可用官方 API 或自建 `ddys-worker-proxy`。
- API 连接测试。
- WordPress transient 缓存。
- 缓存管理页。
- 诊断页。
- 短代码生成器。
- 响应式前端 CSS。
- 无 npm 构建的轻量 Gutenberg 区块。
- 可选认证求片表单，默认关闭。
- DDYS 图标和 WordPress.org 规范图标。

## 短代码

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

## 区块

插件注册这些动态区块：

- DDYS Latest
- DDYS Hot
- DDYS Search
- DDYS Calendar
- DDYS Movie
- DDYS Collection

## 环境要求

- WordPress 6.0+
- Tested up to WordPress 7.0
- PHP 7.4+

## 开发检查

插件使用不需要 npm。仓库内提供结构和安全规则检查脚本：

```bash
node tools/check.mjs
```

## 分发

这个项目不发布 npm。后续使用 GitHub Release 或 WordPress 插件 ZIP 分发。

## License

GPL-2.0-or-later
