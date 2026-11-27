<?php
declare(strict_types=1);

namespace LabPress\I18n;

/**
 * Locale/module isolated translation catalog with a deterministic fallback
 * chain: exact locale → language family → declared fallback → site default → key.
 *
 * This class only reads language files. Dynamic database translations still
 * pass through the existing `translate` filter after this lookup.
 */
final class Translator
{
    /** @var array<string,array<string,mixed>> */
    private static array $catalogs = [];
    /** @var array<string,array<string,mixed>> */
    private static array $metadata = [];
    /** @var array<string,array<int,string>> */
    private static array $chains = [];
    /** @var array<int,array<string,mixed>>|null */
    private static ?array $availableCache = null;

    public static function get(string $key, string $module = 'common', ?string $locale = null): string
    {
        $locale = self::normalizeLocale($locale ?? (function_exists('getLocale') ? (string)getLocale() : ''));
        if ($locale === '') {
            return $key;
        }
        $module = self::normalizeModule($module);
        if ($module === '') {
            return $key;
        }

        foreach (self::chain($locale) as $candidate) {
            $value = self::lookup(self::catalog($candidate, $module), $key);
            if ($value !== null) {
                return $value;
            }
        }
        return $key;
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public static function available(): array
    {
        if (self::$availableCache !== null) {
            return self::$availableCache;
        }
        $dir = self::languageRoot();
        if (!is_dir($dir)) {
            return self::$availableCache = [];
        }
        $languages = [];
        foreach (scandir($dir) ?: [] as $locale) {
            if ($locale === '.' || $locale === '..' || !is_dir($dir . '/' . $locale)) {
                continue;
            }
            if (!preg_match('/^[A-Za-z0-9_-]+$/', $locale)) {
                continue;
            }
            $meta = self::meta($locale);
            if ($meta === []) {
                continue;
            }
            if (!isset($meta['locale'])) {
                $meta['locale'] = $locale;
            }
            $languages[] = $meta;
        }
        return self::$availableCache = $languages;
    }

    public static function reset(): void
    {
        self::$catalogs = [];
        self::$metadata = [];
        self::$chains = [];
        self::$availableCache = null;
    }

    /**
     * @return array<int,string>
     */
    public static function chain(string $locale): array
    {
        $locale = self::normalizeLocale($locale);
        if ($locale === '') {
            return [];
        }
        if (isset(self::$chains[$locale])) {
            return self::$chains[$locale];
        }

        $meta = self::meta($locale);
        $default = self::defaultLocale();
        $family = str_contains($locale, '_') ? explode('_', $locale)[0] : '';
        $fallback = trim((string)($meta['fallback'] ?? ''));
        $chain = [$locale];
        if ($family !== '' && preg_match('/^[A-Za-z]{2,3}$/', $family)) {
            $chain[] = $family;
        }
        if ($fallback !== '') {
            $chain[] = $fallback;
        }
        if ($default !== '') {
            $chain[] = $default;
        }

        $seen = [];
        $result = [];
        foreach ($chain as $item) {
            $item = self::normalizeLocale($item);
            if ($item !== '' && !isset($seen[$item])) {
                $seen[$item] = true;
                $result[] = $item;
            }
        }
        return self::$chains[$locale] = $result;
    }

    /**
     * @return array<string,mixed>
     */
    public static function meta(string $locale): array
    {
        $locale = self::normalizeLocale($locale);
        if ($locale === '' || isset(self::$metadata[$locale])) {
            return self::$metadata[$locale] ?? [];
        }
        $dir = self::languageRoot() . '/' . $locale;
        $json = $dir . '/locale.json';
        if (is_file($json)) {
            $raw = @file_get_contents($json);
            $decoded = is_string($raw) ? json_decode($raw, true) : null;
            if (is_array($decoded)) {
                return self::$metadata[$locale] = $decoded;
            }
        }
        $legacy = $dir . '/lang.config';
        if (is_file($legacy)) {
            $loaded = include $legacy;
            if (is_array($loaded)) {
                return self::$metadata[$locale] = $loaded;
            }
        }
        return self::$metadata[$locale] = [];
    }

    /**
     * @return array<string,mixed>
     */
    private static function catalog(string $locale, string $module): array
    {
        $cacheKey = $locale . '|' . $module;
        if (isset(self::$catalogs[$cacheKey])) {
            return self::$catalogs[$cacheKey];
        }

        $catalog = [];
        if (str_starts_with($module, 'plugin:')) {
            $parts = explode(':', $module, 3);
            $slug = preg_replace('/[^A-Za-z0-9_-]/', '', $parts[1] ?? '');
            $domain = preg_replace('/[^A-Za-z0-9_-]/', '', $parts[2] ?? 'plugin');
            $domain = $domain !== '' ? $domain : 'plugin';
            if ($slug !== '') {
                $dir = LABPRESS_ROOT . '/plugins/' . $slug . '/languages';
                $file = $dir . '/' . $locale . '/' . $domain . '.php';
                if (!is_file($file)) {
                    $file = $dir . '/' . $locale . '.php';
                }
                if (is_file($file)) {
                    $loaded = include $file;
                    if (is_array($loaded)) {
                        $catalog = $loaded;
                    }
                }
            }
        } else {
            $common = self::loadCoreFile($locale, 'common');
            $moduleData = $module === 'common' ? [] : self::loadCoreFile($locale, $module);
            $catalog = array_replace($common, $moduleData);
        }

        return self::$catalogs[$cacheKey] = $catalog;
    }

    /**
     * @return array<string,mixed>
     */
    private static function loadCoreFile(string $locale, string $module): array
    {
        $file = self::languageRoot() . '/' . $locale . '/' . $module . '.php';
        if (!is_file($file)) {
            return [];
        }
        $loaded = include $file;
        return is_array($loaded) ? $loaded : [];
    }

    /**
     * @param array<string,mixed> $catalog
     */
    private static function lookup(array $catalog, string $key): ?string
    {
        $value = $catalog;
        foreach (explode('.', $key) as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return null;
            }
            $value = $value[$segment];
        }
        return is_scalar($value) ? (string)$value : null;
    }

    private static function defaultLocale(): string
    {
        $configured = class_exists(\LabPress::class) ? (string)\LabPress::config('language', 'zh_CN') : '';
        return self::normalizeLocale($configured !== '' ? $configured : 'zh_CN');
    }

    private static function languageRoot(): string
    {
        return defined('LABPRESS_ROOT') ? LABPRESS_ROOT . '/languages' : dirname(__DIR__, 2) . '/languages';
    }

    private static function normalizeLocale(string $locale): string
    {
        $locale = trim($locale);
        return preg_match('/^[A-Za-z0-9_-]{2,20}$/', $locale) ? $locale : '';
    }

    private static function normalizeModule(string $module): string
    {
        $module = trim($module);
        if (str_starts_with($module, 'plugin:')) {
            return preg_match('/^plugin:[A-Za-z0-9_-]+(?::[A-Za-z0-9_-]+)?$/', $module) ? $module : '';
        }
        return preg_match('/^[A-Za-z0-9_-]+$/', $module) ? $module : '';
    }
}
