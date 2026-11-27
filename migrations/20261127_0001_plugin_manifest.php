<?php
declare(strict_types=1);


$columnExists = static function (PDO $pdo, string $table, string $column): bool {
    $rows = $pdo->query('SHOW COLUMNS FROM `' . $table . '`')->fetchAll(PDO::FETCH_ASSOC);
    return in_array($column, array_column($rows, 'Field'), true);
};

return [
    'version' => '20261127_0001_plugin_manifest',
    'description' => 'plugins 表补充 manifest_json，用于缓存 Manifest v2 元数据',
    'up' => static function (PDO $pdo) use ($columnExists): void {
        if (!$columnExists($pdo, 'plugins', 'manifest_json')) {
            $pdo->exec('ALTER TABLE `plugins` ADD COLUMN `manifest_json` JSON NULL AFTER `settings_json`');
        }
    },
    'down' => static function (PDO $pdo) use ($columnExists): void {
        if ($columnExists($pdo, 'plugins', 'manifest_json')) {
            $pdo->exec('ALTER TABLE `plugins` DROP COLUMN `manifest_json`');
        }
    },
];
