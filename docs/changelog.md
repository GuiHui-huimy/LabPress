# Changelog

All notable changes to LabPress will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.1.1] - 2026-11-24

### Added

- `admin_locale` filter in `getLocale()` for an optional, plugin-owned backend locale policy. The core falls back to the site default locale when no plugin handles the filter.
- `admin_topbar_actions` action in the admin topbar, allowing plugins to add compact controls such as a language switcher.
- Dynamic Multi-Language 2.0.2: backend language selection in the login page and admin topbar, persisted in a dedicated `ml_admin_locale` session/cookie, independent from the frontend `lang` cookie.

### Changed

- Core `__()` no longer scans and merges language files from every active plugin. Plugin strings must use the explicit `plugin:{slug}` module, preventing plugin keys from overwriting core UI strings.
- Dynamic Multi-Language menu registration now uses `__('ml_plugin_name', 'plugin:dynamic-multilang')`.
- Admin JavaScript for project management, news management, plugin market and plugin install now reads injected language objects instead of hardcoded Chinese labels.

### Fixed

- Fixed the Dynamic Multi-Language admin page using the non-existent `original_content` key instead of `ml_original_content`.
- Fixed missing `ml_no_translations_yet` interface text in the plugin language packs.
- Fixed hardcoded “个 / 无” strings in the tool version admin view; they now use the `count_unit` and `none` core keys.
- Fixed the admin language switch being ignored on the first request: `getLocale()` was resolved before `loadPlugins()`, so the `admin_locale` filter had not been registered and the site default locale was cached. Locale resolution now runs after plugins are loaded.

## [1.1.0] - 2026-08-14

### Added

- Translation function `__()` now supports explicit plugin language loading using the `plugin:` prefix. Plugin authors can call `__('key', 'plugin:plugin-slug')` to load translations directly from a specific plugin, avoiding key collisions with core language packs.

### Changed

- Plugin language files are now loaded only when explicitly requested or during the automatic scan of active plugins. The automatic scan now sanitizes plugin slugs before building file paths.
- Dynamic Multi-Language plugin language keys now use the `ml_` prefix to prevent conflicts with core language keys.

### Fixed

- Fixed an issue where the Dynamic Multi-Language plugin's language keys (such as `plugin_name`, `about_us`, `research_title`) overwrote core language keys, causing incorrect strings to appear in the admin panel.
- Fixed plugin language file loading path when using the explicit `plugin:` module prefix.

### Documentation

- Updated plugin development documentation to include the `plugin:` explicit language loading syntax and key prefix recommendation.

## [1.0.0] - 2026-08-11

### Added

- Initial stable release of LabPress, an open-source academic CMS designed for research laboratories.

#### Core Features

- Dynamic content management – Edit "About Us", research directions, stats, navigation menus directly from the backend.
- Built-in academic modules – Publications, projects, research news, tools, and version management.
- Hook-driven plugin system – Fully extendable via WordPress-like Action/Filter hooks.
- Comprehensive i18n – Front-end and back-end locales are decoupled; dynamic multi-language support for all user-generated content.
- Markdown editor – Integrated EasyMDE with live preview and split-screen editing.
- Lightweight & zero-dependency – Pure PHP + MySQL, no frameworks or Composer required.
- Plugin ecosystem – Install, activate, update, and uninstall plugins directly from the admin panel.

### Installation

- Clone or download the repository.
- Copy `includes/config-sample.php` to `includes/config.php` and fill in database credentials.
- Import `labpressexample.sql` into MySQL.
- Grant write permissions to `uploads/` and `plugins/` directories.
- Access frontend at `/`, backend at `/admin`.

### Important

- The default administrator account included in the example database must be changed immediately after login.
- This release uses GPL v3 license.