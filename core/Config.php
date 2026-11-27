<?php
declare(strict_types=1);

namespace LabPress;

/**
 * 应用级配置（不是站点内容配置）。
 *
 * 站点内容配置仍由 LabPress::config() 从 site_config 表读取；这里只保存
 * 数据库连接、时区、调试开关、功能开关等部署级配置。
 */
final class Config
{
    /** @var array<string,mixed> */
    private static array $items = [];
    private static bool $loaded = false;

    public static function load(array $items): void
    {
        self::$items = $items;
        self::$loaded = true;
    }

    public static function isLoaded(): bool
    {
        return self::$loaded;
    }

    /**
     * @param string $key 支持点号路径，如 db.host
     * @param mixed $default
     * @return mixed
     */
    public static function get(string $key, $default = null)
    {
        $value = self::$items;
        foreach (explode('.', $key) as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return $default;
            }
            $value = $value[$segment];
        }
        return $value;
    }

    /**
     * @param mixed $value
     */
    public static function set(string $key, $value): void
    {
        $segments = explode('.', $key);
        $target = &self::$items;
        foreach ($segments as $i => $segment) {
            if ($i === count($segments) - 1) {
                $target[$segment] = $value;
                break;
            }
            if (!isset($target[$segment]) || !is_array($target[$segment])) {
                $target[$segment] = [];
            }
            $target = &$target[$segment];
        }
    }

    public static function feature(string $key, bool $default = false): bool
    {
        $features = self::get('features', []);
        if (!is_array($features) || !array_key_exists($key, $features)) {
            return $default;
        }
        return (bool)$features[$key];
    }

    public static function all(): array
    {
        return self::$items;
    }
}
