<?php
declare(strict_types=1);

namespace LabPress\Plugin;

use LabPress\Hooks;
use LabPress\Support\Logger;
use PDO;
use Throwable;

/**
 * 插件管理器 v1。
 *
 * 职责：发现、Manifest v2/旧头注释元数据同步、按 priority 启动、生命周期事件、
 * 依赖检查与异常隔离。不做 Composer 依赖解析，也不做运行时热卸载（PHP 无常驻进程）。
 */
final class Manager
{
    private static ?string $current = null;
    /** @var array<string,bool> */
    private static array $loaded = [];
    /** @var array<string,string> */
    private static array $errors = [];

    public static function root(): string
    {
        return defined('LABPRESS_ROOT') ? LABPRESS_ROOT : dirname(__DIR__, 2);
    }

    public static function db(): PDO
    {
        global $pdo;
        if (!$pdo instanceof PDO) {
            throw new \RuntimeException('Database is not bootstrapped');
        }
        return $pdo;
    }

    /**
     * 扫描 plugins/ 并刷新元数据；不改变启用状态。
     *
     * @return array<int,array<string,mixed>>
     */
    public static function discover(): array
    {
        $dir = self::root() . '/plugins';
        if (!is_dir($dir)) {
            return [];
        }
        $items = [];
        $slugs = @scandir($dir);
        if ($slugs === false) {
            return [];
        }
        $hasNewColumns = self::hasColumns('plugins', ['priority', 'requires_core', 'settings_json', 'data_policy', 'last_synced_at']);
        $hasManifestColumn = self::hasColumns('plugins', ['manifest_json']);
        foreach ($slugs as $slug) {
            if ($slug === '.' || $slug === '..') {
                continue;
            }
            $pluginFile = $dir . '/' . $slug . '/plugin.php';
            if (!is_file($pluginFile) || !preg_match('/^[A-Za-z0-9][A-Za-z0-9_.-]{0,63}$/', $slug)) {
                continue;
            }
            $info = self::parseHeader($pluginFile);
            $info['slug'] = $slug;

            $manifestResult = Manifest::fromDirectory($dir . '/' . $slug, true);
            $manifest = $manifestResult['manifest'];
            $info['manifest'] = $manifest;
            $info['manifest_valid'] = (bool)$manifestResult['ok'];
            $info['manifest_errors'] = $manifestResult['errors'];
            $info['legacy_manifest'] = (bool)$manifestResult['legacy'];
            if ($manifestResult['ok']) {
                $info['name'] = (string)($manifest['name'] ?? $info['name']);
                $info['description'] = (string)($manifest['description'] ?? $info['description']);
                $info['version'] = (string)($manifest['version'] ?? $info['version']);
                $info['author'] = (string)($manifest['author'] ?? $info['author']);
                $info['requires_core'] = (string)($manifest['requires']['core'] ?? '');
            }
            $items[] = $info;

            $exists = self::db()->prepare('SELECT id, version FROM plugins WHERE slug = ?');
            $exists->execute([$slug]);
            $row = $exists->fetch(PDO::FETCH_ASSOC);
            if (!$row) {
                $columns = ['slug', 'name', 'description', 'version', 'author', 'status'];
                $values = [$slug, $info['name'], $info['description'], $info['version'], $info['author'], 0];
                if ($hasNewColumns) {
                    $columns = array_merge($columns, ['priority', 'requires_core', 'last_synced_at']);
                    $values = array_merge($values, [$info['priority'], $info['requires_core'], date('Y-m-d H:i:s')]);
                }
                if ($hasManifestColumn) {
                    $columns[] = 'manifest_json';
                    $values[] = json_encode($manifest, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                }
                $sql = 'INSERT INTO plugins (' . implode(',', $columns) . ') VALUES (' . implode(',', array_fill(0, count($columns), '?')) . ')';
                self::db()->prepare($sql)->execute($values);
                continue;
            }

            $sets = ['name = ?', 'description = ?', 'version = ?', 'author = ?'];
            $values = [$info['name'], $info['description'], $info['version'], $info['author']];
            if ($hasNewColumns) {
                $sets[] = 'priority = ?';
                $sets[] = 'requires_core = ?';
                $sets[] = 'last_synced_at = ?';
                $values[] = $info['priority'];
                $values[] = $info['requires_core'];
                $values[] = date('Y-m-d H:i:s');
            }
            if ($hasManifestColumn) {
                $sets[] = 'manifest_json = ?';
                $values[] = json_encode($manifest, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            }
            $values[] = $slug;
            self::db()->prepare('UPDATE plugins SET ' . implode(', ', $sets) . ' WHERE slug = ?')->execute($values);
        }
        return $items;
    }

    /**
     * @return array<int,string>
     */
    public static function activeSlugs(): array
    {
        $hasPriority = self::hasColumns('plugins', ['priority']);
        $sql = 'SELECT slug FROM plugins WHERE status = 1 ' . ($hasPriority ? 'ORDER BY priority ASC, id ASC' : 'ORDER BY id ASC');
        return self::db()->query($sql)->fetchAll(PDO::FETCH_COLUMN) ?: [];
    }

    /**
     * 启动所有已启用插件。
     *
     * @return array<string,bool>
     */
    public static function boot(): array
    {
        foreach (self::activeSlugs() as $slug) {
            self::load($slug);
        }
        return self::$loaded;
    }

    public static function load(string $slug): bool
    {
        if (isset(self::$loaded[$slug])) {
            return self::$loaded[$slug];
        }
        $slug = self::normalizeSlug($slug);
        if ($slug === '') {
            return false;
        }
        $file = self::root() . '/plugins/' . $slug . '/plugin.php';
        if (!is_file($file)) {
            self::$errors[$slug] = 'plugin.php not found';
            self::$loaded[$slug] = false;
            Logger::warning('Plugin file not found', ['plugin' => $slug]);
            return false;
        }
        try {
            self::with($slug, function () use ($file): void {
                require_once $file;
            });
            $manifestResult = Manifest::fromDirectory(dirname($file), true);
            if ($manifestResult['ok'] && !empty($manifestResult['manifest']['settings']) && !Settings::hasSchema($slug)) {
                Settings::register(
                    $slug,
                    $manifestResult['manifest']['settings'],
                    (string)($manifestResult['manifest']['name'] ?? $slug) . ' 设置'
                );
            }
            self::$loaded[$slug] = true;
        } catch (Throwable $e) {
            self::$loaded[$slug] = false;
            self::$errors[$slug] = $e->getMessage();
            Logger::exception($e, ['plugin' => $slug, 'stage' => 'load']);
        }
        return self::$loaded[$slug];
    }

    public static function activate(string $slug): bool
    {
        $slug = self::normalizeSlug($slug);
        if ($slug === '') {
            return false;
        }
        $stmt = self::db()->prepare('SELECT status, requires_core FROM plugins WHERE slug = ?');
        $stmt->execute([$slug]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            return false;
        }
        if ((int)$row['status'] === 1) {
            return true;
        }

        $manifestResult = Manifest::fromDirectory(self::root() . '/plugins/' . $slug, true);
        if (!$manifestResult['ok']) {
            self::$errors[$slug] = implode(' ', $manifestResult['errors']);
            Logger::warning('Plugin manifest invalid', ['plugin' => $slug, 'errors' => $manifestResult['errors']]);
            return false;
        }
        $manifest = $manifestResult['manifest'];
        $dependencyErrors = Dependencies::errors($slug, $manifest, self::db());
        if ($dependencyErrors !== []) {
            self::$errors[$slug] = implode(' ', $dependencyErrors);
            Logger::warning('Plugin dependency check failed', ['plugin' => $slug, 'errors' => $dependencyErrors]);
            return false;
        }

        if (!self::load($slug)) {
            return false;
        }
        self::db()->prepare('UPDATE plugins SET status = 1 WHERE slug = ?')->execute([$slug]);
        Hooks::doActionForPlugin('plugin_activation', $slug, $slug);
        Hooks::doAction('plugin_activated', $slug);
        Logger::audit('plugin_activate', $slug, 'ok');
        return true;
    }

    public static function deactivate(string $slug): bool
    {
        $slug = self::normalizeSlug($slug);
        if ($slug === '') {
            return false;
        }
        $stmt = self::db()->prepare('SELECT status FROM plugins WHERE slug = ?');
        $stmt->execute([$slug]);
        $status = $stmt->fetchColumn();
        if ($status === false) {
            return false;
        }
        // 如果当前请求已加载该插件，生命周期回调已注册；先通知，再改状态。
        Hooks::doActionForPlugin('plugin_deactivation', $slug, $slug);
        self::db()->prepare('UPDATE plugins SET status = 0 WHERE slug = ?')->execute([$slug]);
        Hooks::doAction('plugin_deactivated', $slug);
        Logger::audit('plugin_deactivate', $slug, 'ok');
        return true;
    }

    public static function uninstall(string $slug): bool
    {
        $slug = self::normalizeSlug($slug);
        if ($slug === '') {
            return false;
        }
        Hooks::doActionForPlugin('plugin_uninstalled', $slug, $slug);
        Logger::audit('plugin_uninstall', $slug, 'ok');
        return true;
    }

    public static function isLoaded(string $slug): bool
    {
        return !empty(self::$loaded[$slug]);
    }

    public static function errors(): array
    {
        return self::$errors;
    }

    public static function current(): ?string
    {
        return self::$current;
    }

    /**
     * 在插件上下文中执行；期间 addAction/addFilter 会记录 plugin slug。
     */
    public static function with(string $slug, callable $callback)
    {
        $previous = self::$current;
        self::$current = $slug;
        try {
            return Hooks::withPlugin($slug, $callback);
        } finally {
            self::$current = $previous;
        }
    }

    /**
     * @return array{name:string,description:string,version:string,author:string,priority:int,requires_core:string}
     */
    public static function parseHeader(string $file): array
    {
        $content = (string)@file_get_contents($file);
        $info = [
            'name' => basename(dirname($file)),
            'description' => '',
            'version' => '',
            'author' => '',
            'priority' => 10,
            'requires_core' => '',
        ];
        foreach ([
            'Plugin Name' => 'name',
            'Description' => 'description',
            'Version' => 'version',
            'Author' => 'author',
            'Requires LabPress' => 'requires_core',
            'Priority' => 'priority',
        ] as $header => $key) {
            if (preg_match('/^\s*\*?\s*' . preg_quote($header, '/') . '\s*:\s*(.+)$/im', $content, $m)) {
                $info[$key] = trim($m[1]);
            }
        }
        $info['priority'] = max(0, min(1000, (int)$info['priority']));
        return $info;
    }

    public static function normalizeSlug(string $slug): string
    {
        return preg_replace('/[^A-Za-z0-9_-]/', '', $slug) ?? '';
    }

    /**
     * @param array<int,string> $columns
     */
    private static function hasColumns(string $table, array $columns): bool
    {
        static $cache = [];
        $key = $table . ':' . implode(',', $columns);
        if (isset($cache[$key])) {
            return $cache[$key];
        }
        try {
            $rows = self::db()->query('SHOW COLUMNS FROM `' . $table . '`')->fetchAll(PDO::FETCH_ASSOC);
            $existing = array_column($rows, 'Field');
            foreach ($columns as $column) {
                if (!in_array($column, $existing, true)) {
                    return $cache[$key] = false;
                }
            }
            return $cache[$key] = true;
        } catch (Throwable $e) {
            return $cache[$key] = false;
        }
    }
}
