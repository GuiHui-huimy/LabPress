<?php
declare(strict_types=1);

return [
    'version' => '20261127_0003_footer_info_slug',
    'description' => 'FooterInfo 改名为 footer-info，保留旧数据并兼容旧 slug',
    'up' => static function (PDO $pdo): void {
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM `plugins` WHERE `slug` = ?');
        $stmt->execute(['footer-info']);
        $hasNew = (int)$stmt->fetchColumn() > 0;

        if ($hasNew) {
            // 已有新 slug（例如测试站重复安装）：停用旧行，避免两个目录同时加载。
            $pdo->prepare('UPDATE `plugins` SET `status` = 0 WHERE `slug` = ?')->execute(['FooterInfo']);
            return;
        }

        $stmt = $pdo->prepare('UPDATE `plugins`
            SET `slug` = ?, `name` = ?, `version` = ?, `requires_core` = ?, `manifest_json` = NULL, `last_synced_at` = NULL
            WHERE `slug` = ?');
        $stmt->execute(['footer-info', 'Footer Info', '3.0.0', '>=1.3.0', 'FooterInfo']);
    },
    'down' => static function (PDO $pdo): void {
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM `plugins` WHERE `slug` = ?');
        $stmt->execute(['FooterInfo']);
        if ((int)$stmt->fetchColumn() > 0) {
            return;
        }
        $pdo->prepare('UPDATE `plugins` SET `slug` = ?, `manifest_json` = NULL, `last_synced_at` = NULL WHERE `slug` = ?')
            ->execute(['FooterInfo', 'footer-info']);
    },
];
