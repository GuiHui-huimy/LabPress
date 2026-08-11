<?php
class LabPress {
    public static function db() {
        global $pdo;
        return $pdo;
    }

    public static function config($key, $default = '') {
        static $config = null;
        if ($config === null) {
            $config = getSiteConfig();   // functions.php 中的函数
        }
        return $config[$key] ?? $default;
    }

    public static function setConfig($key, $value) {
        $db = self::db();
        $stmt = $db->prepare("INSERT INTO site_config (config_key, config_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE config_value = VALUES(config_value)");
        $stmt->execute([$key, $value]);
    }

    public static function registerAdminPage($slug, $title, $callback, $permission = 'all') {
        global $labpress_plugin_pages;
        if (!is_array($labpress_plugin_pages)) $labpress_plugin_pages = [];
        $labpress_plugin_pages[$slug] = compact('title', 'callback', 'permission');
    }

    public static function getAdminPages() {
        global $labpress_plugin_pages;
        return $labpress_plugin_pages ?? [];
    }

    // 注册资源钩子辅助函数
    public static function enqueueStyle($url) {
        LabPress_Hooks::addAction('before_head_end', function() use ($url) {
            echo '<link rel="stylesheet" href="' . htmlspecialchars($url) . '">';
        });
    }

    public static function enqueueScript($url) {
        LabPress_Hooks::addAction('footer_scripts', function() use ($url) {
            echo '<script src="' . htmlspecialchars($url) . '"></script>';
        });
    }
}