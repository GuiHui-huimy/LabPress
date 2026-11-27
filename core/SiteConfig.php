<?php
declare(strict_types=1);

namespace LabPress;

/**
 * site_config 键白名单。
 *
 * 站点配置表不接受任意 key；插件需要新 key 时通过
 * `site_config_allowed_keys` 过滤器显式登记，避免后台写入垃圾键。
 */
final class SiteConfig
{
    /** @var array<int,string> */
    private const CORE_KEYS = [
        'site_name',
        'site_title',
        'title_format',
        'title_separator',
        'about_us',
        'language',
        'research_title',
        'research_subtitle',
        'research_cards',
        'stat_items',
        'stat_developers',
        'stat_layers',
        'stat_tools',
        'stat_workflows',
        // dynamic-multilang 官方插件
        'ml_source_locale',
    ];

    /** @return array<int,string> */
    public static function allowedKeys(): array
    {
        $keys = Hooks::applyFilters('site_config_allowed_keys', self::CORE_KEYS);
        return is_array($keys) ? array_values(array_unique(array_map('strval', $keys))) : self::CORE_KEYS;
    }

    public static function isAllowed(string $key): bool
    {
        return in_array($key, self::allowedKeys(), true);
    }
}
