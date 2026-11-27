<?php
declare(strict_types=1);

namespace LabPress\Admin;

use LabPress\Guard;
use LabPress\Support\Logger;

/**
 * 后台页面注册表。
 *
 * 目标：插件不再通过 ?view= 任意拼接文件，核心也不需要在 index.php 里维护
 * 一份巨大的 if/else。第一阶段保留核心内置菜单，插件页统一从这里取。
 */
final class Registry
{
    /** @var array<string,array{slug:string,title:string,callback:callable,permission:string,capability:?string,parent:?string}> */
    private static array $pages = [];

    public static function register(
        string $slug,
        string $title,
        callable $callback,
        string $permission = 'all',
        ?string $capability = null,
        ?string $parent = null
    ): void {
        $slug = preg_replace('/[^a-zA-Z0-9_-]/', '', $slug);
        if ($slug === '') {
            return;
        }
        self::$pages[$slug] = [
            'slug' => $slug,
            'title' => $title,
            'callback' => $callback,
            'permission' => $permission,
            'capability' => $capability ?? $permission,
            'parent' => $parent,
        ];
    }

    public static function has(string $slug): bool
    {
        return isset(self::$pages[$slug]);
    }

    public static function get(string $slug): ?array
    {
        return self::$pages[$slug] ?? null;
    }

    public static function all(): array
    {
        return self::$pages;
    }

    private static function message(string $key, string $fallback): string
    {
        $text = function_exists('__') ? __($key, 'admin') : $fallback;
        return htmlspecialchars((string)$text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    public static function render(string $slug): void
    {
        $page = self::get($slug);
        if ($page === null) {
            http_response_code(404);
            echo '<p class="p-4">' . self::message('page_not_found', 'Page not found') . '</p>';
            return;
        }
        if (!Guard::hasPermission($page['capability'] ?? $page['permission'])) {
            Logger::audit('admin_page_denied', $slug, 'denied', ['capability' => $page['capability']]);
            echo '<div class="card"><p>' . self::message('no_permission', 'No permission') . '</p></div>';
            return;
        }
        try {
            call_user_func($page['callback']);
        } catch (\Throwable $e) {
            Logger::exception($e, ['admin_page' => $slug]);
            echo '<div class="card"><p>' . self::message('page_error', 'Page error') . '</p></div>';
        }
    }
}
