# Dynamic Multi-Language Plugin

This chapter provides a technical reference for the Dynamic Multi-Language plugin included with LabPress. The plugin extends the core internationalization system by enabling translation of database-stored content, such as navigation menus, slides, site configuration fields, and plugin-specific interface strings.

The chapter is intended for developers and advanced administrators who need to understand how the plugin works, how it stores translations, and how it interacts with the LabPress hook system. It assumes familiarity with the [Internationalization Overview](overview.md) and [Core Language Packs](core-lang.md).

## 1. Overview

The Dynamic Multi-Language plugin solves a limitation of the core language pack system: core language files can only translate static strings that exist in the codebase. They cannot translate content created through the admin panel, such as navigation menu titles, homepage slide text, or the About Us field.

The plugin provides a translation management interface for this database-stored content. It does not modify the original records; instead, it stores translations in a separate table and replaces the output at render time using filters.

The plugin is packaged as a standard LabPress plugin located in:

```
plugins/dynamic-multilang/
```

Its slug, used in the `plugins` table and the plugin language loading mechanism, is `dynamic-multilang`.

## 2. Plugin Structure

The plugin directory contains the following files:

```
dynamic-multilang/
├── plugin.php
├── admin-page.php
├── languages/
│   ├── zh_CN.php
│   └── en_US.php
└── assets/
    └── dm-admin.css
```

- `plugin.php` – main plugin file containing hooks, filters, and utility functions.
- `admin-page.php` – callback for the admin management page.
- `languages/` – plugin language files for interface strings.
- `assets/` – admin styles and scripts.

The plugin header in `plugin.php` declares the plugin name, description, version, and author. This metadata is read by the plugin scanner when the plugin is first registered.

## 3. Translation Storage

All translations are stored in the `ml_translations` table. The table is created during plugin activation and dropped during uninstallation.

The table structure is:

| Column | Type | Description |
|--------|------|-------------|
| `id` | `int` | Primary key, auto-increment |
| `table_name` | `varchar(50)` | Logical source of the original string, such as `nav_menu`, `slides`, `site_config`, `__custom`, or `plugin_dynamic-multilang` |
| `record_id` | `varchar(100)` | Unique identifier of the original record, or the original string itself for custom keys |
| `field_name` | `varchar(100)` | Field within the record, such as `title`, `description`, `config_value`, or `text` |
| `locale` | `varchar(10)` | Target language code, such as `en_US` |
| `translated_value` | `text` | The translated text |

A unique constraint is defined on the combination of `table_name`, `record_id`, `field_name`, and `locale`, preventing duplicate translations for the same source field and language.

## 4. Core Functions

The plugin defines several utility functions in `plugin.php`.

### 4.1 Source Locale Management

```php
function ml_get_source_locale()
```

Returns the current source locale. The source locale represents the language in which the original site content is written. It first checks the `ml_source_locale` key in `site_config`. If that key is not set, it falls back to the site default language.

```php
function ml_set_source_locale($locale)
```

Stores the source locale value in `site_config` using `LabPress::setConfig()`.

### 4.2 Retrieving Translations

```php
function ml_get_translation($table, $recordId, $field, $locale = null)
```

Retrieves a translation from the `ml_translations` table. If `$locale` is not provided, the current frontend locale is used.

The function first checks whether the requested locale is the same as the source locale. If it is, the function returns `null`, indicating that no translation should be applied and the original content should be used.

If the locale is different, the function queries the table for a matching translation. If no row is found, it returns `null`.

### 4.3 Saving Translations

```php
function ml_set_translation($table, $recordId, $field, $locale, $value)
```

Saves a translation. If the value is empty, the corresponding row is deleted from the table. Otherwise, the function performs an upsert using `INSERT ... ON DUPLICATE KEY UPDATE`, updating the existing translation if one is present.

## 5. Filters and Integration

The plugin registers its filters during the `init` action. This ensures that the plugin’s callbacks are only added after all plugins have been loaded.

### 5.1 Navigation Menu Translation

The plugin registers a filter on `nav_menu_item`:

```php
LabPress_Hooks::addFilter('nav_menu_item', function($html, $menu) {
    $id = $menu['id'] ?? null;
    if (!$id) return $html;
    $newTitle = ml_get_translation('nav_menu', $id, 'title');
    if ($newTitle === null) return $html;
    return sprintf('<a href="%s">%s</a>', htmlspecialchars($menu['url']), htmlspecialchars($newTitle));
}, 10, 2);
```

The filter receives the generated HTML for a menu item and the raw menu data array. It checks whether a translation exists for the menu ID in the `nav_menu` table. If found, it regenerates the anchor tag with the translated title.

### 5.2 Slide Translation

The plugin registers a filter on `slides_data`:

```php
LabPress_Hooks::addFilter('slides_data', function($slides) {
    foreach ($slides as &$slide) {
        $id = $slide['id'] ?? null;
        if ($id) {
            $title = ml_get_translation('slides', $id, 'title');
            $desc = ml_get_translation('slides', $id, 'description');
            if ($title) $slide['title'] = $title;
            if ($desc) $slide['description'] = $desc;
        }
    }
    return $slides;
});
```

This filter is applied when the frontend loads slide data from the API. It iterates over each slide and replaces the title and description if translations exist.

### 5.3 Dynamic Text Translation

The plugin registers two filters on `translate` to handle dynamic content.

#### Custom Keys and Site Configuration

```php
LabPress_Hooks::addFilter('translate', function($value, $key) {
    if (defined('IS_ADMIN') || strpos($_SERVER['SCRIPT_NAME'], '/admin/') !== false) {
        return $value;
    }

    $trans = ml_get_translation('__custom', $key, 'text');
    if ($trans) return $trans;

    $siteMap = [
        'about_us'          => 'about_us',
        'about_us_content'  => 'about_us',
        'research_title'    => 'research_title',
        'research_heading'  => 'research_title',
        'research_subtitle' => 'research_subtitle',
        'research_subheading'=> 'research_subtitle',
    ];

    if (isset($siteMap[$key])) {
        $trans = ml_get_translation('site_config', $siteMap[$key], 'config_value');
        if ($trans) return $trans;
    }

    return $value;
}, 10, 2);
```

This filter is the core mechanism for translating user-edited content.

It first checks whether the current request is for the admin panel. If so, it returns the original value immediately, ensuring that administrators always see the source content when editing.

Then it looks for a custom translation in the `__custom` table. The `record_id` is the original string itself. This is used for dynamic text such as statistics labels and research card titles.

If no custom translation exists, the filter applies a mapping of core translation keys to `site_config` fields. For example, the key `about_us` maps to the `about_us` site configuration field. If a translation exists for that field, it is returned.

#### Plugin Interface Translation

A second `translate` filter handles the plugin’s own interface strings:

```php
LabPress_Hooks::addFilter('translate', function($value, $key, $locale) {
    $trans = ml_get_translation('plugin_dynamic-multilang', $key, 'plugin_text', $locale);
    if ($trans !== null) return $trans;
    return $value;
}, 10, 3);
```

This filter checks the `plugin_dynamic-multilang` table for translations of plugin-specific keys. It allows the admin interface of the plugin itself to be translated through the same management page.

### 5.4 Admin Locale and Switchers

The plugin owns the optional backend language policy. It registers a filter on `admin_locale` during plugin load, before any core admin translation is requested:

```php
LabPress_Hooks::addFilter('admin_locale', 'ml_admin_locale', 10, 2);
```

`ml_admin_locale()` resolves the backend locale in this order:

1. A valid `lang` query parameter on an admin request.
2. The `ml_admin_locale` value stored in the current session.
3. The `ml_admin_locale` cookie.
4. The site default locale passed in by the core `getLocale()` function.

A valid selection is written to the session and to a dedicated `ml_admin_locale` cookie. The cookie is separate from the frontend `lang` cookie, so changing the admin language does not change the public site language.

The plugin renders its switcher through two core hooks:

- `admin_topbar_actions` in `admin/index.php`, after the user chip.
- `login_after_form` in `admin/login.php`, below the login and back-home buttons.

If the plugin is inactive or removed, no `admin_locale` callback exists. The core `getLocale()` function then falls back to the site default locale, so the login page and admin console continue to work without any plugin dependency.

## 6. Admin Management Page

The plugin registers an admin page using `LabPress::registerAdminPage()`:

```php
LabPress::registerAdminPage('dynamic-multilang', __('ml_plugin_name', 'plugin:dynamic-multilang'), function() {
    require_once __DIR__ . '/admin-page.php';
});
```

The page is accessible from the admin sidebar and requires the `all` permission. The page title, like the rest of the plugin's interface, is loaded through the explicit `plugin:dynamic-multilang` language module.

The admin page organizes translations into several tabs:

- **Nav Menus** – translations for header and footer menu titles.
- **Slides** – translations for slide titles and descriptions.
- **About / Research** – translations for About Us, research title, and research subtitle.
- **Dynamic Content** – translations for statistics labels and research card text.
- **Plugin Language** – translations for the plugin’s own interface strings.

The page also includes a source locale selector. The selected source locale is saved to `site_config` under the key `ml_source_locale`.

When the form is submitted, the plugin iterates over the submitted translation fields and saves each translation using `ml_set_translation()`.

## 7. Data Flow Example

A typical translation flow for the About Us section follows these steps:

1. The homepage template calls `__('about_us_content', 'index')`.
2. The core `__()` function loads the index language file. If no static translation exists, it passes the original value through the `translate` filter.
3. The Dynamic Multi-Language plugin’s first `translate` filter runs.
4. It checks for a custom translation under `__custom`. If no match is found, it applies the `siteMap` and identifies that the key corresponds to the `about_us` site configuration field.
5. The plugin calls `ml_get_translation('site_config', 'about_us', 'config_value')`.
6. `ml_get_translation` checks whether the current locale is the source locale. If not, it queries the `ml_translations` table.
7. If a translation exists, it is returned. Otherwise, the original value is returned.
8. The homepage displays the translated About Us content.

## 8. Plugin Uninstallation

The plugin registers an uninstall hook:

```php
LabPress_Hooks::addAction('plugin_uninstalled', function($slug) {
    if ($slug === 'dynamic-multilang') {
        $db = LabPress::db();
        $db->exec("DROP TABLE IF EXISTS `ml_translations`");
    }
});
```

When the plugin is uninstalled, the `ml_translations` table is dropped. The plugin directory is then removed by the core plugin uninstall process.

Because the uninstall hook is fired before the plugin files are deleted, the plugin can safely perform cleanup operations.

## 9. Source Locale and Backend Behavior

The source locale is the language in which the original content is stored. By default, it is the site default language, but it can be changed through the plugin's admin page.

The plugin intentionally skips dynamic-content translation when the current request is an admin page. This ensures that administrators always see the original source content in the backend, regardless of the frontend language setting. Backend interface strings are a separate concern: they are selected through the `admin_locale` filter and the plugin's admin language switcher described in section 5.4.

On the frontend, translation is applied only when the current locale differs from the source locale. If the current locale is the same as the source locale, `ml_get_translation()` returns `null`, and the original content is displayed.

## 10. Custom Translation Table Conventions

The plugin uses several logical table names in the `ml_translations` table:

| Table Name | Purpose |
|------------|---------|
| `nav_menu` | Translation of navigation menu items. `record_id` is the menu item ID. |
| `slides` | Translation of slides. `record_id` is the slide ID. |
| `site_config` | Translation of site configuration fields. `record_id` is the configuration key. |
| `__custom` | Translation of arbitrary strings. `record_id` is the original string text. |
| `plugin_dynamic-multilang` | Translation of the plugin’s own interface strings. `record_id` is the original language key. |

The `field_name` column distinguishes between multiple translatable fields for the same record. For example, a slide record may have translations for both `title` and `description`.

## 11. Extensibility

The plugin itself is designed to be extensible through the LabPress hook system. Developers can add additional translation sources or modify the behavior of the existing filters.

Because the plugin uses the standard `translate` filter, other plugins can cooperate or conflict with its translation logic. The order of filter registration may affect which translation takes precedence. The Dynamic Multi-Language plugin registers its filters with priority `10`, so plugins that need to override its behavior can use a higher or lower priority as needed.

Custom content types can be made translatable by registering filters similar to those used for navigation menus and slides. For example, a custom content type could register a filter on its own data hook and call `ml_get_translation()` with an appropriate table name.

## 12. Next Steps

After understanding the Dynamic Multi-Language plugin, you may want to explore:

- [Core Language Packs](core-lang.md) – details about the static translation system.
- [Plugin System Introduction](../plugins-dev/intro.md) – general plugin architecture.
- [Developing Your First Plugin](../plugins-dev/develop.md) – learn how to create custom plugins.
- [Hooks Reference](../plugins-dev/hooks-reference.md) – complete list of available filters and actions.
- [Plugin Examples](../plugins-dev/examples.md) – working examples, including the Dynamic Multi-Language plugin.