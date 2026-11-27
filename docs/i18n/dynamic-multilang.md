# Dynamic Multi-Language Plugin

This chapter describes Dynamic Multi-Language 3.0 in LabPress 1.3.0. The plugin translates database-stored content without modifying the source rows: it stores target-language overrides in `ml_translations`, registers the translatable entities it knows about, and replaces rendered values through filters.

The plugin is located in `plugins/dynamic-multilang/` and uses `dynamic-multilang` as its slug.

## 1. Structure

```
dynamic-multilang/
├── plugin.json                 # Manifest v2
├── plugin.php                  # hooks, filters, entity registration
├── admin-page.php              # translation workbench
├── languages/
│   ├── zh_CN/plugin.php
│   └── en_US/plugin.php
└── assets/
    └── dm-admin.css
```

`plugin.json` is authoritative for metadata and dependencies. The legacy `languages/{locale}.php` files remain as a compatibility fallback; new code reads `languages/{locale}/plugin.php`.

## 2. Translation Storage

The v2 table keeps the legacy columns and adds entity-oriented columns:

| Column | Purpose |
|--------|---------|
| `entity` | Entity name, such as `nav_menu`, `slides`, `site_config`, or `__custom`. |
| `entity_id` | Record ID; SHA-256 for `__custom` text keys. |
| `field` | Field name, such as `title`, `description`, `config_value`, or `text`. |
| `locale` | Target locale. |
| `translated_value` | Translated text. |
| `source_locale` | Locale of the source text. |
| `source_hash` | SHA-256 of the source text. |
| `status` | `current` or `outdated`. |

Legacy `table_name`, `record_id`, and `field_name` columns are dual-written for one migration window. Migrations `20261127_0002_i18n_v2` backfills them and hashes existing `__custom` keys.

The table is a sidecar. Deleting a translation restores the source value; source content is never overwritten.

## 3. Repository API

The plugin delegates all reads and writes to `LabPress\I18n\Repository`:

```php
Repository::get($entity, $id, $field, $locale);
Repository::set($entity, $id, $field, $locale, $value, $sourceValue);
Repository::prefetch($entity, $idsOrNull, $fields, $locale);
Repository::markOutdated($entity, $id, $field, $source);
Repository::invalidate();
```

`prefetch()` loads a whole entity or a list of IDs in one query and records cache misses so later lookups do not fall back to one query per row. `invalidate()` clears the request cache and increments `site_config.i18n_version`.

## 4. Entity Registry

`plugin.php` registers its translatable surfaces during load:

```php
EntityRegistry::register('nav_menu', [
    'label' => 'Navigation menus',
    'table' => 'nav_menu',
    'id_field' => 'id',
    'fields' => [
        'title' => ['label' => 'Title', 'type' => 'text'],
    ],
]);

EntityRegistry::register('__custom', [
    'label' => 'Dynamic content',
    'table' => '',
    'id_field' => 'id',
    'dynamic' => true,
    'fields' => ['text' => ['label' => 'Text', 'type' => 'text']],
]);
```

The registry supplies the workbench tabs and validates table and field names. Plugins should use the registry rather than hard-coding admin forms.

## 5. Runtime Integration

Filters are registered on `init`:

- `nav_menu_item`: prefetches `nav_menu.title` once, then replaces the title for the current locale.
- `slides_data`: prefetches the current slide IDs and translates `title` and `description`.
- `translate`: skips admin requests, checks `__custom.text`, then maps `about_us`, `research_title`, and `research_subtitle` to `site_config.config_value`.

The `translate` filter receives `($value, $key, $locale, $module)` and preserves unknown strings.

## 6. Translation Workbench

`admin-page.php` is registered through `Admin\Registry`; it does not check permissions itself and does not call `hasPermission()`. The core verifies the capability before invoking the callback.

The workbench:

1. Verifies the CSRF token before writing.
2. Saves `t[entity][formId][field][locale]` through `Repository::set()`.
3. Shows per-locale completion counts from `Repository::translatedCount()`.
4. Lets the administrator choose `ml_source_locale`.
5. Prefetches all entity values before rendering the form.

## 7. Language Files

Plugin UI strings are loaded with:

```php
__('ml_plugin_name', 'plugin:dynamic-multilang')
```

Lookup order:

1. `plugins/dynamic-multilang/languages/{locale}/plugin.php`
2. `plugins/dynamic-multilang/languages/{locale}.php` (legacy compatibility)

When a locale has no plugin file, the core falls back through the language family, the declared `locale.json` fallback, and the site default.

## 8. Migration and Compatibility

- Existing `ml_translations` rows are preserved and backfilled by `20261127_0002_i18n_v2`.
- Legacy `ml_get_translation()` / `ml_set_translation()` wrappers remain available and delegate to the repository.
- The plugin no longer drops `ml_translations` on uninstall; data is retained for rollback and migration safety.
- `plugin_dynamic-multilang` legacy rows remain in the database but are no longer read by the new runtime. UI strings come from language files.

## 9. Operational Checks

- Activate the plugin only when the site actually serves more than one content language.
- Run `php bin/migrate.php up` before opening the workbench.
- Use `?lang=en_US` / `?lang=ja_JP` to verify the admin locale switch.
- Confirm the three-language pages contain no literal `ml_` keys.
