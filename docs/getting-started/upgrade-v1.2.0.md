# v1.2.0 Upgrade and Rollback

This page is the operator checklist for the kernel and installation release. It is intentionally short: deploy, migrate, verify, and know how to go back. Do **not** open `install.php` on an existing site; the installer is only for empty databases. Use `php bin/migrate.php up` for upgrades.

## 1. Before Deploying

1. Back up the database and the current `includes/config.php`.
2. Confirm `storage/config/site.php` exists and contains the production database credentials. If it does not, create it from `storage/config/site.example.php`. The legacy `DB_*` constants in `includes/config.php` still work as a fallback, but new deployments should not depend on them.
3. Make `storage/logs/` writable by the PHP-FPM user.
4. Confirm the web server denies direct access to `storage/`. Apache uses the included `.htaccess`; Nginx needs `location ^~ /storage/ { deny all; }`.

## 2. Deploy Order

```bash
# 1. Put the new files in place
# 2. Check syntax
find core includes migrations bin tests plugins -name '*.php' -print0 | xargs -0 -n1 php -l

# 3. Inspect and apply schema changes
php bin/migrate.php status
php bin/migrate.php up

# 4. Verify the site and admin login
curl -I https://your-domain/
curl -I https://your-domain/admin/login.php
```

Do not open the admin panel before `php bin/migrate.php up` completes. The plugin manager expects the new `plugins.priority`, `requires_core`, `settings_json`, `data_policy`, and `last_synced_at` columns.

## 3. Feature Flags

`storage/config/features.php` contains the defaults. Override per deployment in `storage/config/features.local.php`:

```php
<?php
return [
    'plugins.manager_v2' => false,
    'admin.registry_router' => true,
    'hooks.v2' => true,
    'assets.registry' => true,
    'guard.v2' => true,
];
```

`plugins.manager_v2 => false` restores the old `getActivePlugins()` / `syncPlugins()` / `loadPlugins()` path. Use it only as a short-lived rollback while investigating a plugin incompatibility; the database migration is additive, so the old path still reads the same `plugins` table.

## 4. Rollback

### 4.1 Application rollback

Restore the previous release files, keep the migrated database, and set:

```php
<?php
// storage/config/features.local.php
return ['plugins.manager_v2' => false];
```

The old code ignores the new plugin columns. Do not run `php bin/migrate.php down` unless you have checked every migration manually.

### 4.2 Schema rollback

If you must reverse the schema:

```bash
php bin/migrate.php down 1   # categories_legacy -> categories
php bin/migrate.php down 1   # drop ml_translations source columns
php bin/migrate.php down 1   # drop plugins metadata columns
```

`down` is not a full backup. The 0002 rollback shortens `record_id` back to 100 characters and can fail if a translation record uses a longer ID. Take a database dump before running it.

## 5. Post-Deploy Checks

- Front page returns 200 and uses the expected theme.
- `admin/login.php` returns 200 and accepts the current administrator account.
- Dashboard shows the plugin list without SQL errors.
- Activate and deactivate `FooterInfo`; the footer table is created only on activation.
- Open the dynamic multilingual admin page; `dm-admin.css` is emitted once by the asset registry.
- `storage/logs/audit-YYYYMMDD.log` records login and plugin lifecycle events.
- No `storage/logs/app-*.log` entries containing SQL or stack traces from normal requests.

## 6. Known Limits

- The migration runner does not wrap MySQL DDL in a transaction. A failed `ALTER TABLE` can leave a partial change; restore from backup rather than rerunning blindly.
- `categories` is renamed, not dropped. Search custom code for `FROM categories` before deleting `categories_legacy`.
- Plugin activation is idempotent, but a plugin whose `plugin.php` has a parse error fails to load and is not marked active.
- This release does not include marketplace UI or remote one-click installation. Those depend on v1.3.0 Manifest v2 and the v1.4.0 client.
