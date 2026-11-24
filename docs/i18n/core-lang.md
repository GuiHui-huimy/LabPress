# Core Language Packs

This chapter provides a technical reference for the core language pack system in LabPress. It describes how language files are structured, how the translation function works, how modules are loaded, and how the core interacts with the hook system to support both static and dynamic translation workflows.

The information in this chapter is intended for developers and advanced administrators who need to understand, extend, or modify the built-in internationalization behavior. It assumes familiarity with the general i18n architecture described in the [Internationalization Overview](overview.md).

## 1. Overview

LabPress uses a file-based language pack system for all static interface strings. Each supported locale has a dedicated directory under `languages/`, and each functional module has its own PHP file within that directory.

The system is built around a single translation function, `__()`, which:

- Loads language files on demand.
- Resolves translation keys, including nested keys using dot notation.
- Applies the `translate` filter to the resolved value.
- Falls back to the original key when no translation is found.

Language packs are loaded separately from the database-backed translation system provided by the Dynamic Multi-Language plugin. Together, they cover both static and dynamic content.

## 2. Directory Structure

The core language files are organized as follows:

```
languages/
├── zh_CN/
│   ├── lang.config
│   ├── common.php
│   ├── index.php
│   ├── admin.php
│   ├── news.php
│   ├── projects.php
│   ├── tools.php
│   └── publications.php
└── en_US/
    ├── lang.config
    ├── common.php
    ├── index.php
    ├── admin.php
    ├── news.php
    ├── projects.php
    ├── tools.php
    └── publications.php
```

Each language directory contains:

- A `lang.config` file that provides metadata about the language.
- One or more module files named after the corresponding functional area.

Modules are organized by context. For example:

| Module | Purpose |
|--------|---------|
| `common` | Shared strings used across the site, including navigation, footer, buttons, and general labels |
| `index` | Strings specific to the homepage template |
| `admin` | Strings used in the administration panel, including menus, forms, and messages |
| `news` | Strings used by the research news module |
| `projects` | Strings used by the project and category pages |
| `tools` | Strings used by the tools module |
| `publications` | Strings used by the publications module |

Plugins may define their own language directories and are not required to follow the core module names.

## 3. Language Metadata

Each language directory contains a `lang.config` file. This file returns a PHP array with metadata that is used when rendering the frontend language switcher and, when the Dynamic Multi-Language plugin is active, the admin language switcher.

A typical `lang.config` file looks like this:

```php
<?php
return [
    'name'      => 'English',
    'locale'    => 'en_US',
    'flag'      => '🇺🇸',
    'direction' => 'ltr',
];
```

The `locale` value should match the directory name exactly. The `getAvailableLanguages()` function reads this file and appends the `locale` key from the directory name, ensuring consistency.

The metadata is used by:

- The frontend language switcher.
- The **Site Language** dropdown in the admin settings page.
- The Dynamic Multi-Language plugin’s source locale selector.

## 4. Language File Format

Each language file must return an associative array. The keys are translation identifiers, and the values are the translated strings.

A simple language file looks like this:

```php
<?php
return [
    'learn_more'      => 'Learn More',
    'view_details'    => 'View Details',
    'loading'         => 'Loading...',
    'footer_copyright'=> '© 2017-{year} HUI-MY.LAB. All rights reserved.',
];
```

The values may contain placeholders such as `{year}` or `{site}`. These placeholders are replaced by the relevant rendering code at output time, not by the translation function itself.

### 4.1 Nested Keys

Language files may use nested arrays to group related strings. The `__()` function supports accessing nested values using dot notation.

For example, a language file may define:

```php
return [
    'nav' => [
        'home'         => 'Home',
        'tools'        => 'Tools',
        'publications' => 'Publications',
    ],
    'footer' => [
        'links' => [
            'blog'   => 'Blog',
            'forum'  => 'Forum',
        ],
    ],
];
```

To retrieve `Home`, the key would be:

```php
__('nav.home', 'common');
```

The dot notation is resolved by splitting the key on `.` and traversing the array structure.

## 5. The Translation Function

The core translation function is defined in `includes/functions.php`:

```php
function __($key, $module = 'common')
```

It accepts two parameters:

- `$key` – the translation key. Supports dot notation for nested arrays.
- `$module` – the module name. Defaults to `common`.

The function maintains static caches for:

- Loaded core modules.
- Merged core language data.

This design ensures that language files are loaded only once per PHP request.

## 6. Module Loading

The translation function loads language files in a specific order.

### 6.1 Common Module

The `common` module is loaded first and always. On the first call to `__()`, the system loads the common language file for the current locale and merges it into the local language array.

### 6.2 Explicit Core Module

If the `$module` argument is a core module name other than `common`, the corresponding file is loaded on demand.

For example:

```php
__('page_title', 'publications');
```

This call loads `languages/{locale}/publications.php` and merges its contents into the current request’s language data.

### 6.3 Plugin Language Modules

Plugin language files are **never merged into the core language namespace automatically**. The core `__()` function only loads files under `languages/{locale}/`, plus the explicitly requested plugin file when the module starts with `plugin:`.

```php
__('ml_plugin_name', 'plugin:dynamic-multilang');
```

When the module begins with `plugin:`, the translation function:

1. Extracts the plugin directory name from the module string.
2. Builds the path to the plugin's language file.
3. Loads only that file.
4. Resolves the key within that file's returned array.
5. Returns the result directly without merging the plugin language data into the global core language array.

This explicit loading method isolates plugin translations and prevents a plugin language file from overwriting core keys. A plugin that is deactivated or whose directory is removed simply has no language callback; core strings keep coming from the root language packs.

## 7. Key Resolution and Fallback

After the necessary language data has been loaded, the translation function resolves the requested key.

If the key contains dot notation, it is split into segments. Each segment is used to traverse the language array. If a segment is missing, the function returns the original key and applies the `translate` filter.

If the key exists, the resolved value is passed through the `translate` filter before being returned.

The fallback behavior is important. If a language file is missing or incomplete, the site does not crash. Instead, untranslated strings appear in their original key form. This makes it easy to identify missing translations during development.

## 8. The `translate` Filter

Every value returned by `__()` is passed through the `translate` filter.

```php
return LabPress_Hooks::applyFilters('translate', $value, $key, $locale, $module);
```

The filter receives four arguments:

- `$value` – the resolved translation or the original key if no translation was found.
- `$key` – the original translation key.
- `$locale` – the current locale.
- `$module` – the requested module.

Plugins can register on this filter to modify translations dynamically. The Dynamic Multi-Language plugin uses this filter to replace database-stored content with translated values.

## 9. Locale Detection

The current locale is determined by the `getLocale()` function. The function uses a static cache to avoid repeated detection within a single request.

The detection logic depends on the request context.

### 9.1 Admin Context

In the admin area, the function always returns the site default language. This is determined by checking the `IS_ADMIN` constant or the script path.

### 9.2 Frontend Context

In the frontend, the locale is selected in the following priority order:

1. The `lang` URL parameter.
2. The `lang` cookie.
3. The site default language stored in `site_config`.

If the `lang` parameter is present and valid, the selected locale is stored in a cookie for future visits.

## 10. Language Discovery

The `getAvailableLanguages()` function scans the `languages/` directory and reads each subdirectory’s `lang.config` file.

The returned array contains one entry per language, with the `locale` key set to the directory name.

This function is used by:

- The frontend language switcher.
- The admin language settings page.
- The Dynamic Multi-Language plugin.

Adding a new language is therefore as simple as creating a new directory with a valid `lang.config` file and the necessary language files.

## 11. Adding a New Language

To add a new core language:

1. Create a new directory under `languages/`. Use the locale code as the directory name, for example `fr_FR`.
2. Create a `lang.config` file with the language metadata.
3. Create a `common.php` file with the shared interface strings.
4. Create additional module files as needed: `index.php`, `admin.php`, `news.php`, `projects.php`, `tools.php`, and `publications.php`.
5. If the language should be available in the admin panel, ensure that the `admin.php` file covers all admin interface keys.

The new language will be detected automatically by `getAvailableLanguages()` and will appear in the frontend language switcher. It also appears in the admin switcher while the Dynamic Multi-Language plugin is active, because the plugin renders that control and handles `admin_locale`; without the plugin the backend stays on the site default language.

## 12. Integration with the Hook System

The core language system exposes several hooks that allow plugins to interact with translation data.

| Hook | Type | Description |
|------|------|-------------|
| `translate` | Filter | Applied to every string returned by `__()`. Can be used to replace strings with translations from other sources. |
| `admin_locale` | Filter | Applied to the backend default locale. Receives the default locale and the list of available locale codes. Returning an invalid locale falls back to the site default, so core works without a locale plugin. |
| `page_title_full` | Filter | Applied to the generated full page title. Can be used to customize title output. |

Plugin developers should use these hooks instead of modifying core language files. This preserves compatibility and ensures that custom translation logic remains independent of the core.

## 13. Related Functions

The following functions are part of the core language system and are defined in `includes/functions.php`:

| Function | Description |
|----------|-------------|
| `__($key, $module = 'common')` | Returns a translated string. |
| `_e($key, $module = 'common')` | Echoes a translated string. |
| `getLocale()` | Returns the current locale. |
| `getAvailableLanguages()` | Returns metadata for all available languages. |
| `getFullTitle($pageTitle = '', $suffix = '')` | Generates a complete page title using the configured format. |

These functions form the foundation of the static translation system and are used throughout the core templates and admin panel.

## 14. Next Steps

After understanding the core language pack system, continue with:

- [Dynamic Multi-Language Plugin](dynamic-multilang.md) – learn how database-stored content is translated.
- [Plugin Development](../plugins-dev/intro.md) – understand how plugins can provide their own language files.
- [Hooks Reference](../plugins-dev/hooks-reference.md) – see the complete list of available hooks.
- [Plugin Examples](../plugins-dev/examples.md) – view working examples of plugins that use the translation system.