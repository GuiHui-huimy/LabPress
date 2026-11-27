<?php
declare(strict_types=1);


$columnExists = static function (PDO $pdo, string $table, string $column): bool {
    $rows = $pdo->query('SHOW COLUMNS FROM `' . $table . '`')->fetchAll(PDO::FETCH_ASSOC);
    return in_array($column, array_column($rows, 'Field'), true);
};

return [
    'version' => '20261125_0001_plugin_columns',
    'description' => 'plugins 表补充 priority/requires_core/settings_json/data_policy/last_synced_at',
    'up' => static function (PDO $pdo) use ($columnExists): void {
        $columns = [
            'priority' => "ALTER TABLE `plugins` ADD COLUMN `priority` SMALLINT NOT NULL DEFAULT 10 AFTER `status`",
            'requires_core' => "ALTER TABLE `plugins` ADD COLUMN `requires_core` VARCHAR(50) NULL AFTER `priority`",
            'settings_json' => "ALTER TABLE `plugins` ADD COLUMN `settings_json` JSON NULL AFTER `requires_core`",
            'data_policy' => "ALTER TABLE `plugins` ADD COLUMN `data_policy` VARCHAR(20) NULL AFTER `settings_json`",
            'last_synced_at' => "ALTER TABLE `plugins` ADD COLUMN `last_synced_at` DATETIME NULL AFTER `data_policy`",
        ];
        foreach ($columns as $column => $sql) {
            if (!$columnExists($pdo, 'plugins', $column)) {
                $pdo->exec($sql);
            }
        }
    },
    'down' => static function (PDO $pdo) use ($columnExists): void {
        foreach (['last_synced_at', 'data_policy', 'settings_json', 'requires_core', 'priority'] as $column) {
            if ($columnExists($pdo, 'plugins', $column)) {
                $pdo->exec('ALTER TABLE `plugins` DROP COLUMN `' . $column . '`');
            }
        }
    },
];
