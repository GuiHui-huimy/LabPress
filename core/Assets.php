<?php
declare(strict_types=1);

namespace LabPress;

/**
 * 资源注册表：统一登记样式/脚本，按 URL 去重，支持依赖顺序。
 * 只负责登记与渲染，不负责下载或打包。
 */
final class Assets
{
    /** @var array<string,array{url:string,deps:array,version:?string,media:string}> */
    private static array $styles = [];
    /** @var array<string,array{url:string,deps:array,version:?string,footer:bool}> */
    private static array $scripts = [];
    private static bool $stylesRendered = false;
    private static bool $scriptsRendered = false;

    public static function addStyle(string $url, array $deps = [], ?string $version = null, string $media = 'all'): void
    {
        $url = trim($url);
        if ($url === '') {
            return;
        }
        self::$styles[$url] = compact('url', 'deps', 'version', 'media');
    }

    public static function addScript(string $url, array $deps = [], ?string $version = null, bool $footer = true): void
    {
        $url = trim($url);
        if ($url === '') {
            return;
        }
        self::$scripts[$url] = compact('url', 'deps', 'version', 'footer');
    }

    public static function styles(): array
    {
        return self::$styles;
    }

    public static function scripts(): array
    {
        return self::$scripts;
    }

    public static function renderStyles(): void
    {
        if (self::$stylesRendered || empty(self::$styles)) {
            return;
        }
        self::$stylesRendered = true;
        foreach (self::sortStyles() as $item) {
            $href = htmlspecialchars((string)$item['url'], ENT_QUOTES, 'UTF-8');
            if ($item['version'] !== null && $item['version'] !== '') {
                $href .= (strpos($href, '?') === false ? '?' : '&') . 'v=' . rawurlencode($item['version']);
            }
            echo '<link rel="stylesheet" href="' . $href . '" media="' . htmlspecialchars((string)$item['media'], ENT_QUOTES, 'UTF-8') . '">' . "\n";
        }
    }

    public static function renderScripts(bool $footer = true): void
    {
        if (self::$scriptsRendered || empty(self::$scripts)) {
            return;
        }
        self::$scriptsRendered = true;
        foreach (self::sortScripts() as $item) {
            if ($item['footer'] !== $footer) {
                continue;
            }
            $src = htmlspecialchars((string)$item['url'], ENT_QUOTES, 'UTF-8');
            if ($item['version'] !== null && $item['version'] !== '') {
                $src .= (strpos($src, '?') === false ? '?' : '&') . 'v=' . rawurlencode($item['version']);
            }
            echo '<script src="' . $src . '"></script>' . "\n";
        }
    }

    public static function reset(): void
    {
        self::$styles = [];
        self::$scripts = [];
        self::$stylesRendered = false;
        self::$scriptsRendered = false;
    }

    private static function sortStyles(): array
    {
        return self::sortByDeps(self::$styles);
    }

    private static function sortScripts(): array
    {
        return self::sortByDeps(self::$scripts);
    }

    /**
     * 依赖只保证“被依赖项先输出”；循环依赖时退回注册顺序，不死循环。
     */
    private static function sortByDeps(array $items): array
    {
        $sorted = [];
        $visiting = [];
        $resolved = [];
        $visit = function (string $url) use (&$visit, &$sorted, &$visiting, &$resolved, $items): void {
            if (isset($resolved[$url]) || !isset($items[$url])) {
                return;
            }
            if (isset($visiting[$url])) {
                return;
            }
            $visiting[$url] = true;
            foreach ((array)($items[$url]['deps'] ?? []) as $dep) {
                if (is_string($dep)) {
                    $visit($dep);
                }
            }
            unset($visiting[$url]);
            $resolved[$url] = true;
            $sorted[] = $items[$url];
        };
        foreach (array_keys($items) as $url) {
            $visit((string)$url);
        }
        return $sorted;
    }
}
