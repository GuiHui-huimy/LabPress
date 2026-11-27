<?php
declare(strict_types=1);

return [
    'version' => '20261127_0004_official_plugin_versions',
    'description' => '同步官方插件 3.0.0 版本与核心依赖',
    'up' => static function (PDO $pdo): void {
        $stmt = $pdo->prepare('UPDATE `plugins` SET `version` = ?, `requires_core` = ?, `manifest_json` = NULL, `last_synced_at` = NULL WHERE `slug` = ?');
        $stmt->execute(['3.0.0', '>=1.3.0', 'dynamic-multilang']);
        $stmt->execute(['3.0.0', '>=1.3.0', 'footer-info']);
    },
    'down' => static function (PDO $pdo): void {
        // Versions are metadata; no destructive rollback.
    },
];
