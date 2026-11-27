<?php
declare(strict_types=1);

namespace LabPress\Plugin;

use PDO;

/**
 * Plugin dependency checks used before activation.
 *
 * This is intentionally a small resolver: direct requirements and conflicts
 * are checked; the admin activates dependencies one by one. A full topological
 * installer belongs to the later plugin-market client.
 */
final class Dependencies
{
    /**
     * @param array<string,mixed> $manifest
     * @return array<int,string>
     */
    public static function errors(string $slug, array $manifest, ?PDO $pdo = null): array
    {
        $errors = [];
        $requires = is_array($manifest['requires'] ?? null) ? $manifest['requires'] : [];
        $core = trim((string)($requires['core'] ?? ''));
        $php = trim((string)($requires['php'] ?? ''));

        if ($core !== '' && defined('LABPRESS_VERSION') && !self::satisfies((string)LABPRESS_VERSION, $core)) {
            $errors[] = '需要 LabPress ' . $core . '，当前为 ' . LABPRESS_VERSION . '。';
        }
        if ($php !== '' && !self::satisfies(PHP_VERSION, $php)) {
            $errors[] = '需要 PHP ' . $php . '，当前为 ' . PHP_VERSION . '。';
        }

        $plugins = is_array($requires['plugins'] ?? null) ? $requires['plugins'] : [];
        if ($plugins !== []) {
            $db = $pdo ?? self::db();
            foreach ($plugins as $dependency => $constraint) {
                $dependency = (string)$dependency;
                $constraint = trim((string)$constraint);
                if ($dependency === '' || $dependency === $slug) {
                    continue;
                }
                $stmt = $db->prepare('SELECT slug, status, version, manifest_json FROM plugins WHERE slug = ? LIMIT 1');
                $stmt->execute([$dependency]);
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                if (!$row || (int)($row['status'] ?? 0) !== 1) {
                    $provider = self::activeProvider($db, $dependency);
                    if ($provider !== null) {
                        $row = $provider;
                    }
                }
                if (!$row) {
                    $errors[] = '缺少依赖插件：' . $dependency . '。';
                    continue;
                }
                if ((int)($row['status'] ?? 0) !== 1) {
                    $errors[] = '依赖插件未启用：' . $dependency . '。';
                    continue;
                }
                if ($constraint !== '' && $constraint !== '*' && !self::satisfies((string)$row['version'], $constraint)) {
                    $errors[] = '依赖插件 ' . $dependency . ' 需要 ' . $constraint . '，当前为 ' . $row['version'] . '。';
                }
            }
        }

        $conflicts = is_array($manifest['conflicts'] ?? null) ? $manifest['conflicts'] : [];
        if ($conflicts !== []) {
            $db = $pdo ?? self::db();
            foreach ($conflicts as $conflict) {
                $conflict = (string)$conflict;
                if ($conflict === '' || $conflict === $slug) {
                    continue;
                }
                $stmt = $db->prepare('SELECT status FROM plugins WHERE slug = ? LIMIT 1');
                $stmt->execute([$conflict]);
                if ((int)($stmt->fetchColumn() ?: 0) === 1) {
                    $errors[] = '与已启用插件冲突：' . $conflict . '。';
                }
            }
        }

        return $errors;
    }

    /**
     * Supports the common version constraint forms used by manifests.
     * Bare versions are exact matches; `*` and `any` match everything.
     */
    public static function satisfies(string $version, string $constraint): bool
    {
        $version = ltrim(trim($version), 'vV');
        $constraint = trim($constraint);
        if ($constraint === '' || $constraint === '*' || $constraint === 'any') {
            return true;
        }
        if (str_contains($constraint, '||')) {
            foreach (explode('||', $constraint) as $part) {
                if (self::satisfies($version, $part)) {
                    return true;
                }
            }
            return false;
        }

        if (preg_match('/^\^(.+)$/', $constraint, $m)) {
            $base = ltrim(trim($m[1]), 'vV');
            if (!self::satisfies($version, '>=' . $base)) {
                return false;
            }
            $parts = explode('.', preg_replace('/[^0-9.].*$/', '', $base));
            $major = (int)($parts[0] ?? 0);
            return version_compare($version, ($major + 1) . '.0.0', '<');
        }
        if (preg_match('/^~(.+)$/', $constraint, $m)) {
            $base = ltrim(trim($m[1]), 'vV');
            if (!self::satisfies($version, '>=' . $base)) {
                return false;
            }
            $parts = explode('.', preg_replace('/[^0-9.].*$/', '', $base));
            $major = (int)($parts[0] ?? 0);
            $minor = (int)($parts[1] ?? 0);
            return version_compare($version, $major . '.' . ($minor + 1) . '.0', '<');
        }
        if (str_ends_with($constraint, '.*')) {
            $prefix = substr($constraint, 0, -2);
            return preg_match('/^' . preg_quote($prefix, '/') . '(?:\.|$)/', $version) === 1;
        }
        if (preg_match('/^(>=|<=|>|<|==|=)\s*(.+)$/', $constraint, $m)) {
            return version_compare($version, ltrim(trim($m[2]), 'vV'), $m[1]);
        }

        return version_compare($version, $constraint, '==');
    }

    private static function db(): PDO
    {
        global $pdo;
        if (!$pdo instanceof PDO) {
            throw new \RuntimeException('Database is not bootstrapped');
        }
        return $pdo;
    }

    /**
     * Find an active plugin that declares `provides: ["name"]` in manifest_json.
     *
     * @return array<string,mixed>|null
     */
    private static function activeProvider(PDO $db, string $provided): ?array
    {
        try {
            $rows = $db->query('SELECT slug, status, version, manifest_json FROM plugins WHERE status = 1')->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Throwable) {
            return null;
        }
        foreach ($rows as $row) {
            $manifest = json_decode((string)($row['manifest_json'] ?? ''), true);
            if (!is_array($manifest)) {
                continue;
            }
            $provides = is_array($manifest['provides'] ?? null) ? $manifest['provides'] : [];
            if (in_array($provided, array_map('strval', $provides), true)) {
                return $row;
            }
        }
        return null;
    }
}
