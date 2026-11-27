# Upgrading to v1.3.0

v1.3.0 adds the plugin contract, the i18n engine, and both official plugin 3.0 rewrites. Existing content and translations are preserved.

## 1. Before Deploying

1. Back up the database and `storage/config/site.php`.
2. Confirm PHP 8.1 or newer and the `curl`, `zip`, `mbstring`, and `pdo_mysql` extensions.
3. Deploy the v1.3.0 files. The old `plugins/FooterInfo/` directory is replaced by `plugins/footer-info/`.
4. Run the migration:

```bash
php bin/migrate.php up
```

The expected final schema version is `20261127_0004_official_plugin_versions`.

## 2. What the Migration Changes

- **`ml_translations` v2.** Adds `entity`, `entity_id`, and `field`; backfills them from `table_name`, `record_id`, and `field_name`; hashes existing `__custom` keys. Legacy columns remain.
- **Footer Info slug.** Renames the `FooterInfo` database row to `footer-info` and updates its version to `3.0.0`. If both rows exist, the old row is deactivated.
- **Official plugin versions.** Sets both official rows to `3.0.0` and `requires >=1.3.0`.

The old `footer_info` table and legacy translation columns are **not dropped**. They are retained for rollback and one migration window.

## 3. Plugin Changes

- **Footer Info 3.0** stores settings in `plugins.settings_json`. On first load it copies the legacy `footer_info` row if no settings exist. The admin page is `?view=footer-info-settings` and is rendered by the Settings API.
- **Dynamic Multi-Language 3.0** reads/writes through `LabPress\I18n\Repository` and registers entities with `EntityRegistry`. The workbench is `?view=dynamic-multilang`; it uses CSRF protection and batch prefetch.
- Plugin admin pages no longer need their own `hasPermission()` check. The Admin Registry checks permission before invoking the callback.

## 4. Language Packs

- Core language lookup now prefers `languages/{locale}/{module}.php` with a fallback chain.
- Plugin UI strings prefer `plugins/{slug}/languages/{locale}/plugin.php`; legacy `languages/{locale}.php` files still work.
- `ja_JP` is introduced with `locale.json` and falls back to `en_US`.

To verify coverage:

```bash
php tools/i18n-check.php
```

## 5. Remote Plugin Installation

Remote installation is disabled by default. To enable it, configure both keys in `site_config`:

```php
LabPress::setConfig('plugin_remote_install_enabled', '1');
LabPress::setConfig('plugin_remote_install_hosts', 'plugins.example.com');
```

Requests to `api/plugin-install-from-url.php` must include a `sha256` value. Downloads are limited to 20MB, validated at every redirect hop, and rejected for private/reserved IPs or unlisted hosts.

## 6. Post-Upgrade Checks

- Visit the homepage in `zh_CN`, `en_US`, and `ja_JP`; no literal translation keys should appear.
- Open **Plugins → Installed Plugins** and confirm both official plugins are `3.0.0`.
- Open Footer Info settings and confirm the old footer text was migrated.
- Open Dynamic Multi-Language and confirm existing translations are visible.
- Save one translation and confirm `site_config.i18n_version` increases.
- Confirm the footer and navigation still render on the frontend.
