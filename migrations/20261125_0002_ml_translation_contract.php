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

return [
    'version' => '20261125_0002_ml_translation_contract',
    'description' => 'ml_translations 补充 source_locale/source_hash/status，并扩展 record_id',
    'up' => static function (PDO $pdo) use ($tableExists, $columnExists): void {
        if (!$tableExists($pdo, 'ml_translations')) {
            return;
        }
        $pdo->exec('ALTER TABLE `ml_translations` MODIFY COLUMN `record_id` VARCHAR(255) NOT NULL');
        $columns = [
            'source_locale' => "ALTER TABLE `ml_translations` ADD COLUMN `source_locale` VARCHAR(10) NULL AFTER `locale`",
            'source_hash' => "ALTER TABLE `ml_translations` ADD COLUMN `source_hash` CHAR(64) NULL AFTER `source_locale`",
            'status' => "ALTER TABLE `ml_translations` ADD COLUMN `status` VARCHAR(20) NOT NULL DEFAULT 'current' AFTER `source_hash`",
        ];
        foreach ($columns as $column => $sql) {
            if (!$columnExists($pdo, 'ml_translations', $column)) {
                $pdo->exec($sql);
            }
        }
    },
    'down' => static function (PDO $pdo) use ($tableExists, $columnExists): void {
        if (!$tableExists($pdo, 'ml_translations')) {
            return;
        }
        $pdo->exec('ALTER TABLE `ml_translations` MODIFY COLUMN `record_id` VARCHAR(100) NOT NULL');
        foreach (['status', 'source_hash', 'source_locale'] as $column) {
            if ($columnExists($pdo, 'ml_translations', $column)) {
                $pdo->exec('ALTER TABLE `ml_translations` DROP COLUMN `' . $column . '`');
            }
        }
    },
];
