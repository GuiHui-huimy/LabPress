<?php
declare(strict_types=1);

namespace LabPress\I18n;

use PDO;

/**
 * Translation store v2.
 *
 * v2 columns: entity / entity_id / field / locale / source_locale /
 * source_hash / status / translated_value. Legacy table_name / record_id /
 * field_name columns are kept in sync for one migration window.
 */
final class Repository
{
    /** @var array<string,string|false> */
    private static array $prefetch = [];
    /** @var array<string,bool> */
    private static array $prefetchScopes = [];
    private static ?bool $hasV2 = null;

    public static function get(string $entity, string $id, string $field, ?string $locale = null): ?string
    {
        $locale = self::normalizeLocale($locale ?? (function_exists('getLocale') ? (string)getLocale() : ''));
        if ($locale === '' || $locale === self::sourceLocale()) {
            return null;
        }
        $originalId = $id;
        $id = self::entityId($entity, $id);
        $legacyId = $entity === '__custom' ? $originalId : $id;
        $key = self::cacheKey($entity, $id, $field, $locale);

        if (array_key_exists($key, self::$prefetch)) {
            return self::$prefetch[$key] === false ? null : (string)self::$prefetch[$key];
        }
        $scope = $entity . '|' . $locale;
        if (!empty(self::$prefetchScopes[$scope])) {
            return null;
        }

        $db = self::db();
        if (self::hasV2()) {
            $stmt = $db->prepare('SELECT translated_value FROM ml_translations WHERE entity=? AND entity_id=? AND field=? AND locale=? LIMIT 1');
            $stmt->execute([$entity, $id, $field, $locale]);
            $value = $stmt->fetchColumn();
            if ($value !== false) {
                return (string)$value;
            }
        }

        // Legacy fallback while a site has not run the v2 migration yet.
        $stmt = $db->prepare('SELECT translated_value FROM ml_translations WHERE table_name=? AND record_id=? AND field_name=? AND locale=? LIMIT 1');
        $stmt->execute([$entity, $legacyId, $field, $locale]);
        $value = $stmt->fetchColumn();
        return $value !== false ? (string)$value : null;
    }

    public static function set(
        string $entity,
        string $id,
        string $field,
        string $locale,
        string $value,
        string $sourceValue = ''
    ): bool {
        $locale = self::normalizeLocale($locale);
        if ($locale === '') {
            return false;
        }
        $originalId = $id;
        $id = self::entityId($entity, $id);
        $legacyId = $entity === '__custom' ? $originalId : $id;
        $db = self::db();
        unset(self::$prefetch[self::cacheKey($entity, $id, $field, $locale)]);

        if ($value === '') {
            if (self::hasV2()) {
                $stmt = $db->prepare('DELETE FROM ml_translations WHERE entity=? AND entity_id=? AND field=? AND locale=?');
                $stmt->execute([$entity, $id, $field, $locale]);
            }
            $stmt = $db->prepare('DELETE FROM ml_translations WHERE table_name=? AND record_id=? AND field_name=? AND locale=?');
            $stmt->execute([$entity, $legacyId, $field, $locale]);
            return true;
        }

        $hash = $sourceValue !== '' ? hash('sha256', $sourceValue) : null;
        if (self::hasV2()) {
            $sql = 'INSERT INTO ml_translations
                (table_name, record_id, field_name, locale, translated_value, source_locale, source_hash, status, entity, entity_id, field)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE translated_value=VALUES(translated_value),
                    source_locale=VALUES(source_locale), source_hash=VALUES(source_hash),
                    status=VALUES(status), entity=VALUES(entity), entity_id=VALUES(entity_id), field=VALUES(field)';
            $stmt = $db->prepare($sql);
            return $stmt->execute([
                $entity, $legacyId, $field, $locale, $value, self::sourceLocale(), $hash, 'current', $entity, $id, $field,
            ]);
        }

        $stmt = $db->prepare('INSERT INTO ml_translations (table_name, record_id, field_name, locale, translated_value) VALUES (?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE translated_value=VALUES(translated_value)');
        return $stmt->execute([$entity, $legacyId, $field, $locale, $value]);
    }

    /**
     * Load a whole entity or selected IDs in one query.
     *
     * @param array<int|string>|null $ids
     * @param array<int,string> $fields
     */
    public static function prefetch(string $entity, ?array $ids, array $fields, ?string $locale = null): void
    {
        $locale = self::normalizeLocale($locale ?? (function_exists('getLocale') ? (string)getLocale() : ''));
        if ($locale === '' || !self::hasV2()) {
            return;
        }
        $fields = array_values(array_filter($fields, static fn($f): bool => is_string($f) && preg_match('/^[A-Za-z0-9_]{1,64}$/', $f)));
        if ($fields === []) {
            return;
        }

        $scope = $entity . '|' . $locale;
        if ($ids === null && !empty(self::$prefetchScopes[$scope])) {
            return;
        }
        if ($ids !== null) {
            $ids = array_values(array_map(static fn($id): string => self::entityId($entity, (string)$id), $ids));
            if ($ids === []) {
                return;
            }
            $allCached = true;
            foreach ($ids as $id) {
                foreach ($fields as $field) {
                    if (!array_key_exists(self::cacheKey($entity, (string)$id, (string)$field, $locale), self::$prefetch)) {
                        $allCached = false;
                        break 2;
                    }
                }
            }
            if ($allCached) {
                return;
            }
        }

        $db = self::db();
        $sql = 'SELECT entity_id, field, translated_value FROM ml_translations WHERE entity=? AND locale=? AND field IN (' . implode(',', array_fill(0, count($fields), '?')) . ')';
        $params = array_merge([$entity, $locale], $fields);
        if ($ids !== null) {
            $sql .= ' AND entity_id IN (' . implode(',', array_fill(0, count($ids), '?')) . ')';
            $params = array_merge($params, $ids);
            foreach ($ids as $id) {
                foreach ($fields as $field) {
                    self::$prefetch[self::cacheKey($entity, (string)$id, (string)$field, $locale)] = false;
                }
            }
        } else {
            self::$prefetchScopes[$scope] = true;
        }

        try {
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $key = self::cacheKey($entity, (string)$row['entity_id'], (string)$row['field'], $locale);
                self::$prefetch[$key] = (string)$row['translated_value'];
            }
        } catch (\Throwable) {
            // The table may not exist before plugin activation; keep source text.
        }
    }

    /**
     * Mark translations as outdated when their source text changed.
     */
    public static function markOutdated(string $entity, string $id, string $field, string $source): int
    {
        if (!self::hasV2() || $source === '') {
            return 0;
        }
        $hash = hash('sha256', $source);
        $stmt = self::db()->prepare('UPDATE ml_translations SET status=? WHERE entity=? AND entity_id=? AND field=? AND source_hash IS NOT NULL AND source_hash <> ?');
        $stmt->execute(['outdated', $entity, self::entityId($entity, $id), $field, $hash]);
        return $stmt->rowCount();
    }

    public static function translatedCount(string $entity, string $locale): int
    {
        $locale = self::normalizeLocale($locale);
        if ($entity === '' || $locale === '') {
            return 0;
        }
        try {
            if (self::hasV2()) {
                $stmt = self::db()->prepare("SELECT COUNT(*) FROM ml_translations WHERE entity=? AND locale=? AND translated_value IS NOT NULL AND translated_value <> ''");
                $stmt->execute([$entity, $locale]);
            } else {
                $stmt = self::db()->prepare("SELECT COUNT(*) FROM ml_translations WHERE table_name=? AND locale=? AND translated_value IS NOT NULL AND translated_value <> ''");
                $stmt->execute([$entity, $locale]);
            }
            return (int)$stmt->fetchColumn();
        } catch (\Throwable) {
            return 0;
        }
    }

    public static function invalidate(): void
    {
        self::$prefetch = [];
        self::$prefetchScopes = [];
        if (class_exists(\LabPress::class) && \LabPress\SiteConfig::isAllowed('i18n_version')) {
            $version = (int)\LabPress::config('i18n_version', 0);
            \LabPress::setConfig('i18n_version', $version + 1);
        }
    }

    public static function version(): int
    {
        return class_exists(\LabPress::class) ? (int)\LabPress::config('i18n_version', 0) : 0;
    }

    public static function sourceLocale(): string
    {
        if (class_exists(\LabPress::class) && \LabPress\SiteConfig::isAllowed('ml_source_locale')) {
            return (string)\LabPress::config('ml_source_locale', \LabPress::config('language', 'zh_CN'));
        }
        return class_exists(\LabPress::class) ? (string)\LabPress::config('language', 'zh_CN') : 'zh_CN';
    }

    private static function hasV2(): bool
    {
        if (self::$hasV2 !== null) {
            return self::$hasV2;
        }
        try {
            $rows = self::db()->query('SHOW COLUMNS FROM `ml_translations`')->fetchAll(PDO::FETCH_ASSOC);
            $fields = array_column($rows, 'Field');
            return self::$hasV2 = in_array('entity', $fields, true)
                && in_array('entity_id', $fields, true)
                && in_array('field', $fields, true);
        } catch (\Throwable) {
            return self::$hasV2 = false;
        }
    }

    private static function entityId(string $entity, string $id): string
    {
        return $entity === '__custom' ? hash('sha256', $id) : $id;
    }

    private static function cacheKey(string $entity, string $id, string $field, string $locale): string
    {
        return $entity . '|' . $id . '|' . $field . '|' . $locale;
    }

    private static function normalizeLocale(string $locale): string
    {
        return preg_match('/^[A-Za-z0-9_-]{2,20}$/', trim($locale)) ? trim($locale) : '';
    }

    private static function db(): PDO
    {
        global $pdo;
        if (!$pdo instanceof PDO) {
            throw new \RuntimeException('Database is not bootstrapped');
        }
        return $pdo;
    }
}
