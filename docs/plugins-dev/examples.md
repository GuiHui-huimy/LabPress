# Plugin Examples

This chapter shows the shipped plugins in LabPress 1.3.0 and the patterns they use. The examples focus on the current APIs: Manifest v2, `Settings`, `Admin\Registry`, `I18n\Repository`, and `I18n\EntityRegistry`.

The default checkout contains:

- `footer-info` — Footer Info 3.0, using the Plugin Settings API.
- `dynamic-multilang` — Dynamic Multi-Language 3.0, using the v2 translation repository and entity registry.

A minimal teaching plugin can be created under any valid slug, for example `hello-word`.

## 1. Minimal Plugin

```php
<?php
/**
 * Plugin Name: Hello World
 * Description: Adds a footer greeting and a small admin page.
 * Version: 1.0.0
 * Author: LabPress
 * Requires LabPress: >=1.3.0
 */

LabPress_Hooks::addAction('footer_scripts', function () {
    echo '<p style="text-align:center;color:#888">Powered by LabPress + Hello World</p>';
});

LabPress::registerAdminPage('hello-settings', 'Hello Settings', function () {
    echo '<div class="card"><h3>Hello World</h3><p>Plugin page rendered through Admin Registry.</p></div>';
});
```

A single `plugin.php` is valid. It is discovered by `Manager::discover()` and can be activated from the admin panel.

## 2. Manifest v2

Plugins should add `plugin.json` next to `plugin.php`:

```json
{
  "slug": "hello-word",
  "name": "Hello World",
  "version": "1.0.0",
  "description": "Small example plugin.",
  "author": "LabPress",
  "license": "GPL-3.0-or-later",
  "requires": {"core": ">=1.3.0", "php": ">=8.1"},
  "provides": ["hello-word"],
  "conflicts": []
}
```

The manifest is validated before installation and before activation. A legacy package without `plugin.json` still works through the `plugin.php` header, but new packages should use Manifest v2.

## 3. Footer Info 3.0

Footer Info demonstrates the Settings API and a zero-loss migration from the old `footer_info` table.

### 3.1 Manifest

`plugins/footer-info/plugin.json` declares slug `footer-info`, version `3.0.0`, a core requirement of `>=1.3.0`, and a settings schema. The schema is also registered in PHP so the page is available immediately after activation.

### 3.2 Settings API

```php
Settings::register(
    'footer-info',
    $footerInfoSchema,
    __('footer_info_title', 'plugin:footer-info'),
    'all',
    'footer-info-settings'
);
```

The registered page is rendered by `Admin\Registry`. The plugin does not check `hasPermission()` itself and does not render a raw HTML form; the core handles CSRF, labels, validation, and persistence in `plugins.settings_json`.

Field labels can declare `label_module` so `Settings` translates them at render time:

```php
['key' => 'content', 'type' => 'textarea', 'label' => 'footer_info_content', 'label_module' => 'plugin:footer-info']
```

### 3.3 Legacy Migration

The old table is read once and copied into settings when `settings_json` has no content:

```php
$legacy = footer_info_legacy();
if (trim((string)($legacy['content'] ?? '')) !== '') {
    Settings::save('footer-info', $legacy);
}
```

The table is not dropped. Existing sites keep the old row for rollback; fresh installs keep the default settings content.

### 3.4 Frontend

```php
LabPress_Hooks::addAction('footer_scripts', function () {
    $settings = Settings::all('footer-info');
    $content = str_replace(
        ['{year}', '{site_name}'],
        [date('Y'), LabPress::config('site_name', 'LabPress')],
        (string)$settings['content']
    );
    // render the lines with the configured alignment, size, color, and padding
});
```

## 4. Dynamic Multi-Language 3.0

### 4.1 Entities

The plugin registers database surfaces instead of hard-coding tabs:

```php
EntityRegistry::register('slides', [
    'label' => 'Slides',
    'table' => 'slides',
    'id_field' => 'id',
    'fields' => [
        'title' => ['label' => 'Title'],
        'description' => ['label' => 'Description'],
    ],
]);
```

### 4.2 Repository

```php
Repository::get('nav_menu', $id, 'title', $locale);
Repository::set('nav_menu', $id, 'title', $locale, $value, $source);
Repository::prefetch('slides', $slideIds, ['title', 'description'], $locale);
```

For custom text keys, the source string is passed as the ID. The repository hashes it internally and keeps the original value in the legacy `record_id` column:

```php
Repository::get('__custom', 'Open notes', 'text', 'en_US');
```

### 4.3 Filters

```php
LabPress_Hooks::addFilter('slides_data', function ($slides) {
    $ids = array_column($slides, 'id');
    Repository::prefetch('slides', $ids, ['title', 'description']);
    foreach ($slides as &$slide) {
        $slide['title'] = Repository::get('slides', (string)$slide['id'], 'title') ?? $slide['title'];
    }
    return $slides;
});
```

The `translate` filter handles `__custom` keys and the `site_config` field map. Admin requests are skipped so the workbench always shows source text.

### 4.4 Workbench

`admin-page.php` is an Admin Registry callback. It verifies CSRF, saves values through `Repository::set()`, prefetches entity values, and shows completion counts. It does not call `hasPermission()`.

## 5. Patterns

### 5.1 Settings Instead of Custom Tables

Use `Settings` for plugin configuration. It avoids a table per plugin and gives the core one validation and storage path. Keep a legacy table only for migration windows.

### 5.2 Registry Instead of Permission Checks

Register admin pages through `LabPress::registerAdminPage()`. The core checks login, permission, and exceptions. A callback should render content, not repeat permission checks.

### 5.3 Prefetch Before Loops

Call `Repository::prefetch()` once before rendering a list. `get()` then reads the request dictionary and does not issue one query per item.

### 5.4 Language Pack Paths

Use `plugins/{slug}/languages/{locale}/plugin.php`. Keep the legacy `languages/{locale}.php` file only while older installations are still supported.

### 5.5 Manifest and Header Together

`plugin.json` is authoritative. Keep a `plugin.php` header for legacy scanners and human readers; do not let the two disagree on slug or version.

## 6. Slugs and Directories

The slug must match the directory name:

- `footer-info` → `plugins/footer-info/`
- `dynamic-multilang` → `plugins/dynamic-multilang/`
- `hello-word` → `plugins/hello-word/`

The `FooterInfo` directory is legacy. Migration `20261127_0003_footer_info_slug` renames its database row and keeps the old slug readable for one version.

## 7. Checks

- `plugin.json` validates with `Manifest::fromDirectory()`.
- The plugin activates with `Manager::activate()`.
- The admin page loads through `Admin\Registry`.
- Settings save with a valid CSRF token.
- Dynamic translations survive `php bin/migrate.php up`.
- Three-language pages contain no literal translation keys.
