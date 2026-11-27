# Installation Guide

This guide covers a fresh LabPress 1.2.0 installation with the browser installer. The installer creates the database schema, administrator account, deployment configuration and optional demo content; you no longer import a SQL dump.

If you are upgrading an existing LabPress site, read the [v1.2.0 upgrade guide](upgrade-v1.2.0.md) instead. Do not run the installer against a database that already contains production content.

## 1. Requirements

| Component | Minimum | Recommended | Notes |
| --- | --- | --- | --- |
| Web server | Apache 2.4 / Nginx 1.18 | Current stable | URL rewriting is optional. |
| PHP | 8.1 | 8.1 or 8.2 | CLI and web SAPI both need the same extensions. |
| Database | MySQL 5.7 / MariaDB 10.3 | MySQL 8.0 / MariaDB 10.6 | `utf8mb4` is required. |
| Disk | Code + uploads | Backups on separate storage | Images and plugin archives grow over time. |
| HTTPS | Recommended | Required for production | Protects login credentials and sessions. |

Required PHP extensions:

| Extension | Purpose |
| --- | --- |
| `pdo` + `pdo_mysql` | Database connection and schema creation. |
| `json` | Site configuration, permissions and plugin metadata. |
| `mbstring` | Multibyte strings in content and installer validation. |
| `session` | Installer CSRF token and admin login state. |
| `zip` | Installing plugins from a ZIP archive (recommended). |

`fileinfo` is recommended for uploads. If `zip` is missing, the installer still works; only the plugin ZIP installer is unavailable.

## 2. Upload the Code

Place the LabPress files in the web root, or in a subdirectory. The examples below assume the domain root:

```text
https://example.com/install.php
```

The web server must be able to write to:

```text
storage/
uploads/
plugins/
```

On Linux/macOS:

```bash
chmod -R 755 uploads/ plugins/
chmod -R 775 storage/
```

## 3. Create an Empty Database

Create a MySQL/MariaDB database with `utf8mb4`:

```sql
CREATE DATABASE labpress
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;
```

Create a dedicated database user and grant it access:

```sql
CREATE USER 'labpress_user'@'localhost' IDENTIFIED BY 'replace-with-a-strong-password';
GRANT ALL PRIVILEGES ON labpress.* TO 'labpress_user'@'localhost';
FLUSH PRIVILEGES;
```

The installer can create the database automatically when the user has the `CREATE DATABASE` privilege. On shared hosting, create the database in the control panel first and leave the installer to create only the tables.

> Use an empty database. The installer uses `CREATE TABLE IF NOT EXISTS` and will not delete existing tables, but mixing old dumps with a new install is not supported.

## 4. Run the Browser Installer

Open:

```text
https://example.com/install.php
```

The first screen runs environment checks. Required checks must pass before the form can be submitted. Fill in:

### Database

| Field | Example | Notes |
| --- | --- | --- |
| Host | `127.0.0.1` | Use `localhost` for a local socket. |
| Port | `3306` | Ignored when a Unix socket is supplied. |
| Unix Socket | `/var/run/mysqld/mysqld.sock` | Optional; takes precedence over host/port. |
| Database name | `labpress` | Letters, digits and underscores only. |
| Database user | `labpress_user` | Needs create/alter/index privileges on the database. |
| Database password | — | Empty password is allowed but not recommended. |

### Site and administrator

| Field | Notes |
| --- | --- |
| Site name | Written to `site_config.site_name`; can be changed later. |
| Site URL | Full `http://` or `https://` URL, without a trailing slash. |
| Site language | `zh_CN` or `en_US`; controls the initial content locale. |
| Admin username | 3–32 characters: letters, digits, `_`, `.`, `@`, `-`. |
| Admin password | At least 8 characters; stored with `password_hash()`. |

### Demo content

The checkbox installs a newly written “computational biology lab” demo: 6 projects, 4 sample publications, 3 news posts, 3 tools, project categories, navigation, homepage sections and footer text. Demo content is not the old `labpressexample.sql` dump. Leave it unchecked for a blank site with only the administrator and base configuration.

After a successful install the page shows the administrator, database, schema version and site URL. The installer writes:

```text
storage/config/site.php   # database credentials and runtime settings
storage/install.lock      # installation marker and schema version
```

`storage/config/site.php` is ignored by Git and should be readable only by the web server user.

## 5. CLI Installation

The same installer is available from the command line for CI and automated deployments:

```bash
php bin/install.php \
  --db-host=127.0.0.1 \
  --db-port=3306 \
  --db-name=labpress \
  --db-user=labpress_user \
  --db-pass='replace-with-a-strong-password' \
  --site-url=https://example.com \
  --site-name='Example Lab' \
  --admin-username=admin \
  --admin-password='replace-with-a-strong-password' \
  --demo
```

Use `--no-demo` for a blank site. Values can also be supplied through `LABPRESS_DB_HOST`, `LABPRESS_DB_PORT`, `LABPRESS_DB_SOCKET`, `LABPRESS_DB_NAME`, `LABPRESS_DB_USER`, `LABPRESS_DB_PASS`, `LABPRESS_SITE_URL`, `LABPRESS_SITE_NAME`, `LABPRESS_ADMIN_USER` and `LABPRESS_ADMIN_PASS`.

The CLI installer exits non-zero and prints the failing stage when validation, connection, schema, seed, config or lock writing fails.

## 6. First Login

Open:

```text
https://example.com/admin/login.php
```

Use the administrator account created in the installer. There is no built-in default password.

Recommended first steps:

1. Change the administrator password if you used a temporary one.
2. Review **Settings → Site Configuration** and navigation menus.
3. Replace demo text and images, or delete demo content you do not need.
4. Enable only the plugins you actually use.
5. Configure HTTPS and restrict access to `storage/`, `includes/` and `docs/`.

## 7. Post-Install Security

1. **Remove or block `install.php`.** The install lock prevents a second run, but production sites should not expose the installer. For Nginx:

   ```nginx
   location = /install.php { return 404; }
   ```

   For Apache, add a rule in `.htaccess` or delete `install.php` after installation.

2. **Protect deployment config.** Confirm `storage/config/site.php` is not readable from the browser. LabPress ships `storage/.htaccess` and `storage/config/.htaccess`; Nginx users need an equivalent deny rule.

3. **Use a dedicated database user.** Grant privileges only on the LabPress database, not global `ALL PRIVILEGES`.

4. **Keep `storage/install.lock`.** Removing it allows the installer to run again; only remove it after backing up and intentionally resetting the site.

5. **Back up the database and `storage/config/site.php`.** They are the two pieces needed to restore a deployment.

## 8. Troubleshooting

### “数据库用户名或密码不正确”

Check the host, port, database name, user and password. For a local socket, verify the socket path exists and the PHP process can access it.

### “数据库不存在，且当前用户没有创建数据库的权限”

Create the empty database in the hosting panel, then run the installer with the same database name. The installer will connect to the existing database and create tables.

### “storage/config 不可写”

Give the web server user write access to `storage/` and `storage/config/`:

```bash
chmod -R 775 storage
```

### Installer says “尚未安装” fails

If `storage/install.lock` exists, the installer intentionally refuses to run. Restore a backup or remove the lock only when you intend to reinstall against an empty database.

### Table creation fails with a collation error

Use `utf8mb4` and `utf8mb4_unicode_ci`. LabPress no longer ships a MySQL 8-specific dump with `utf8mb4_0900_ai_ci`, so the installer is compatible with MariaDB 10.3+.

### HTTP 500 after installation

Check `storage/logs/app-*.log`. The most common causes are an incorrect `storage/config/site.php`, missing `pdo_mysql`, or a PHP version below 8.1.

## 9. Verification Checklist

- [ ] `/install.php` shows the success page once, then “已安装” on later visits.
- [ ] `storage/config/site.php` and `storage/install.lock` exist.
- [ ] `php bin/migrate.php status` shows all migrations as `applied`.
- [ ] `/` returns HTTP 200 and the selected demo content is visible.
- [ ] `/admin/login.php` accepts the installer-created administrator.
- [ ] The dashboard shows the v1.2.0 ECharts overview (content mix, project categories, 14-day trend).
- [ ] `storage/logs/` is writable by the web server user.
