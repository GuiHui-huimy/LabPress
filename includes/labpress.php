<?php
require_once __DIR__ . '/bootstrap.php';

class LabPress
{
    private static ?array $siteConfig = null;

    public static function db()
    {
        global $pdo;
        return $pdo;
    }

    public static function config($key, $default = '')
    {
        if (self::$siteConfig === null) {
            self::$siteConfig = getSiteConfig();
        }
        return self::$siteConfig[$key] ?? $default;
    }

    public static function setConfig($key, $value)
    {
        if (!\LabPress\SiteConfig::isAllowed((string)$key)) {
            throw new \InvalidArgumentException('Unknown site_config key: ' . $key);
        }
        $db = self::db();
        $stmt = $db->prepare("INSERT INTO site_config (config_key, config_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE config_value = VALUES(config_value)");
        $stmt->execute([$key, $value]);
        if (self::$siteConfig !== null) {
            self::$siteConfig[(string)$key] = $value;
        }
    }

    /**
     * 注册后台页面。v1.2.0 起统一进入 Admin\Registry，不再维护全局数组。
     */
    public static function registerAdminPage($slug, $title, $callback, $permission = 'all', $capability = null, $parent = null)
    {
        \LabPress\Admin\Registry::register((string)$slug, (string)$title, $callback, (string)$permission, $capability, $parent);
    }

    public static function getAdminPages()
    {
        return \LabPress\Admin\Registry::all();
    }

    public static function enqueueStyle($url, $deps = [], $version = null, $media = 'all')
    {
        \LabPress\Assets::addStyle((string)$url, (array)$deps, $version === null ? null : (string)$version, (string)$media);
    }

    public static function enqueueScript($url, $deps = [], $version = null, $footer = true)
    {
        \LabPress\Assets::addScript((string)$url, (array)$deps, $version === null ? null : (string)$version, (bool)$footer);
    }

    /**
     * v1.3.0 插件设置：注册 schema 后由核心渲染表单，值存 plugins.settings_json。
     */
    public static function registerSettings($slug, $schema, $title = '', $permission = 'all', $pageSlug = '')
    {
        \LabPress\Plugin\Settings::register((string)$slug, (array)$schema, (string)$title, (string)$permission, (string)$pageSlug);
    }

    public static function settings($slug, $key, $default = null)
    {
        return \LabPress\Plugin\Settings::get((string)$slug, (string)$key, $default);
    }
}
