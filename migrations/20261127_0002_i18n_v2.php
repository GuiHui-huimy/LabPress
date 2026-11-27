<?php
declare(strict_types=1);

$tableExists = static function (PDO $pdo, string $table): bool {
    $stmt = $pdo->query('SHOW TABLES LIKE ' . $pdo->quote($table));
    return (bool)$stmt->fetchColumn();
};
$columnExists = static function (PDO $pdo, string $table, string $column): bool {
    $rows = $pdo->query('SHOW COLUMNS FROM `' . $table . '`')->fetchAll(PDO::FETCH_ASSOC);
    return in_array($column, array_column($rows, 'Field'), true);
};
$indexExists = static function (PDO $pdo, string $table, string $index): bool {
    $rows = $pdo->query('SHOW INDEX FROM `' . $table . '`')->fetchAll(PDO::FETCH_ASSOC);
    return in_array($index, array_column($rows, 'Key_name'), true);
};

return [
    'version' => '20261127_0002_i18n_v2',
    'description' => 'ml_translations 迁移到 entity/entity_id/field 模型，并建立 v2 唯一索引',
    'up' => static function (PDO $pdo) use ($tableExists, $columnExists, $indexExists): void {
        if (!$tableExists($pdo, 'ml_translations')) {
            return;
        }
        $columns = [
            'entity' => "ALTER TABLE `ml_translations` ADD COLUMN `entity` VARCHAR(50) NOT NULL DEFAULT '' AFTER `table_name`",
            'entity_id' => "ALTER TABLE `ml_translations` ADD COLUMN `entity_id` VARCHAR(255) NOT NULL DEFAULT '' AFTER `record_id`",
            'field' => "ALTER TABLE `ml_translations` ADD COLUMN `field` VARCHAR(100) NOT NULL DEFAULT '' AFTER `field_name`",
        ];
        foreach ($columns as $column => $sql) {
            if (!$columnExists($pdo, 'ml_translations', $column)) {
                $pdo->exec($sql);
            }
        }
        $pdo->exec("UPDATE `ml_translations` SET `entity` = `table_name`, `entity_id` = `record_id`, `field` = `field_name` WHERE `entity` = '' OR `entity_id` = '' OR `field` = ''");
        $pdo->exec("UPDATE `ml_translations` SET `entity_id` = SHA2(`record_id`, 256) WHERE `entity` = '__custom'");

        if (!$indexExists($pdo, 'ml_translations', 'unique_translation_v2')) {
            $pdo->exec('ALTER TABLE `ml_translations` ADD UNIQUE KEY `unique_translation_v2` (`entity`,`entity_id`,`field`,`locale`)');
        }
        if (!$indexExists($pdo, 'ml_translations', 'idx_ml_entity_locale')) {
            $pdo->exec('ALTER TABLE `ml_translations` ADD KEY `idx_ml_entity_locale` (`entity`,`locale`)');
        }
    },
    'down' => static function (PDO $pdo) use ($tableExists, $columnExists, $indexExists): void {
        if (!$tableExists($pdo, 'ml_translations')) {
            return;
        }
        if ($indexExists($pdo, 'ml_translations', 'unique_translation_v2')) {
            $pdo->exec('ALTER TABLE `ml_translations` DROP INDEX `unique_translation_v2`');
        }
        if ($indexExists($pdo, 'ml_translations', 'idx_ml_entity_locale')) {
            $pdo->exec('ALTER TABLE `ml_translations` DROP INDEX `idx_ml_entity_locale`');
        }
        foreach (['field', 'entity_id', 'entity'] as $column) {
            if ($columnExists($pdo, 'ml_translations', $column)) {
                $pdo->exec('ALTER TABLE `ml_translations` DROP COLUMN `' . $column . '`');
            }
        }
    },
];
