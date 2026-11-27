<?php
declare(strict_types=1);

namespace LabPress\I18n;

/**
 * Translation entity registry.
 *
 * A plugin declares which database table/fields are translatable. The admin
 * workbench reads this registry instead of hard-coding tabs and field lists.
 */
final class EntityRegistry
{
    /** @var array<string,array<string,mixed>> */
    private static array $entities = [];

    /**
     * @param array<string,mixed> $definition
     */
    public static function register(string $name, array $definition): void
    {
        $name = trim($name);
        if (!preg_match('/^[A-Za-z0-9_.-]{2,50}$/', $name)) {
            return;
        }
        $table = (string)($definition['table'] ?? '');
        if ($table !== '' && !preg_match('/^[A-Za-z0-9_]{1,64}$/', $table)) {
            return;
        }
        $idField = (string)($definition['id_field'] ?? 'id');
        if (!preg_match('/^[A-Za-z0-9_]{1,64}$/', $idField)) {
            return;
        }
        $fields = [];
        foreach ((array)($definition['fields'] ?? []) as $field => $meta) {
            if (!is_string($field) || !preg_match('/^[A-Za-z0-9_]{1,64}$/', $field)) {
                continue;
            }
            if (!is_array($meta)) {
                $meta = [];
            }
            $sourceField = (string)($meta['source'] ?? $field);
            if (!preg_match('/^[A-Za-z0-9_]{1,64}$/', $sourceField)) {
                $sourceField = $field;
            }
            $fields[$field] = [
                'label' => (string)($meta['label'] ?? $field),
                'source' => $sourceField,
                'type' => (string)($meta['type'] ?? 'text'),
            ];
        }
        if ($fields === []) {
            return;
        }

        self::$entities[$name] = [
            'name' => $name,
            'label' => (string)($definition['label'] ?? $name),
            'table' => $table,
            'id_field' => $idField,
            'fields' => $fields,
            'dynamic' => (bool)($definition['dynamic'] ?? false),
        ];
    }

    public static function get(string $name): ?array
    {
        return self::$entities[$name] ?? null;
    }

    /**
     * @return array<string,array<string,mixed>>
     */
    public static function all(): array
    {
        return self::$entities;
    }

    /**
     * Read the source value from the entity table. For dynamic/custom entities
     * the caller supplies the value directly.
     */
    public static function source(string $name, string $id, string $field): string
    {
        $entity = self::get($name);
        if ($entity === null || empty($entity['table'])) {
            return '';
        }
        $entityField = $entity['fields'][$field]['source'] ?? '';
        if ($entityField === '') {
            return '';
        }
        $table = (string)$entity['table'];
        $idField = (string)$entity['id_field'];
        try {
            $db = self::db();
            $stmt = $db->prepare('SELECT `' . $entityField . '` FROM `' . $table . '` WHERE `' . $idField . '` = ? LIMIT 1');
            $stmt->execute([$id]);
            $value = $stmt->fetchColumn();
            return $value !== false ? (string)$value : '';
        } catch (\Throwable) {
            return '';
        }
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public static function rows(string $name): array
    {
        $entity = self::get($name);
        if ($entity === null || empty($entity['table'])) {
            return [];
        }
        $table = (string)$entity['table'];
        try {
            return self::db()->query('SELECT * FROM `' . $table . '` ORDER BY `' . (string)$entity['id_field'] . '` ASC')->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\Throwable) {
            return [];
        }
    }

    private static function db(): \PDO
    {
        global $pdo;
        if (!$pdo instanceof \PDO) {
            throw new \RuntimeException('Database is not bootstrapped');
        }
        return $pdo;
    }
}
