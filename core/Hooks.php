<?php
declare(strict_types=1);

namespace LabPress;

use LabPress\Support\Logger;
use Throwable;

/**
 * Hook Engine v2。
 *
 * 兼容旧版 LabPress_Hooks 的三参数调用，同时增加：
 * - remove / has / did / currentHook / collect
 * - acceptedArgs（null=接收全部参数，保持旧行为）
 * - 回调异常隔离，单个插件抛异常不会白屏
 * - 插件上下文，用于 plugin_{stage} 只通知目标插件
 */
final class Hooks
{
    /** @var array<string,array<int,array<int,array{callback:callable,accepted:?int,plugin:?string}>>> */
    private static array $actions = [];
    /** @var array<string,array<int,array<int,array{callback:callable,accepted:?int,plugin:?string}>>> */
    private static array $filters = [];
    /** @var array<string,int> */
    private static array $did = [];
    /** @var array<int,string> */
    private static array $current = [];
    private static ?string $pluginContext = null;

    public static function addAction(string $hook, callable $callback, int $priority = 10, ?int $acceptedArgs = null): void
    {
        self::$actions[$hook][$priority][] = [
            'callback' => $callback,
            'accepted' => $acceptedArgs,
            'plugin' => self::$pluginContext,
        ];
        ksort(self::$actions[$hook]);
    }

    public static function addFilter(string $hook, callable $callback, int $priority = 10, ?int $acceptedArgs = null): void
    {
        self::$filters[$hook][$priority][] = [
            'callback' => $callback,
            'accepted' => $acceptedArgs,
            'plugin' => self::$pluginContext,
        ];
        ksort(self::$filters[$hook]);
    }

    public static function removeAction(string $hook, callable $callback, ?int $priority = null): bool
    {
        return self::removeFrom(self::$actions, $hook, $callback, $priority);
    }

    public static function removeFilter(string $hook, callable $callback, ?int $priority = null): bool
    {
        return self::removeFrom(self::$filters, $hook, $callback, $priority);
    }

    public static function hasAction(string $hook): bool
    {
        return !empty(self::$actions[$hook]);
    }

    public static function hasFilter(string $hook): bool
    {
        return !empty(self::$filters[$hook]);
    }

    public static function didAction(string $hook): int
    {
        return self::$did[$hook] ?? 0;
    }

    public static function currentHook(): string
    {
        return self::$current ? self::$current[count(self::$current) - 1] : '';
    }

    public static function doAction(string $hook, ...$args): void
    {
        self::dispatch(self::$actions, $hook, $args, false);
    }

    /**
     * 只触发某个插件注册的回调，用于 plugin_activation / plugin_deactivation /
     * plugin_uninstalled 这类生命周期事件，避免误通知其他插件。
     */
    public static function doActionForPlugin(string $hook, string $plugin, ...$args): void
    {
        self::dispatch(self::$actions, $hook, $args, false, $plugin);
    }

    public static function doFilterForPlugin(string $hook, string $plugin, $value, ...$args)
    {
        return self::dispatch(self::$filters, $hook, array_merge([$value], $args), true, $plugin);
    }

    public static function applyFilters(string $hook, $value, ...$args)
    {
        return self::dispatch(self::$filters, $hook, array_merge([$value], $args), true);
    }

    /**
     * 在某个插件上下文中执行代码，期间注册的钩子会记录 plugin slug。
     */
    public static function withPlugin(string $plugin, callable $callback)
    {
        $previous = self::$pluginContext;
        self::$pluginContext = $plugin;
        try {
            return $callback();
        } finally {
            self::$pluginContext = $previous;
        }
    }

    /**
     * 调试/后台面板用：返回钩子注册统计，不返回闭包源码。
     */
    public static function collect(): array
    {
        $summary = ['actions' => [], 'filters' => []];
        foreach (self::$actions as $hook => $priorities) {
            $summary['actions'][$hook] = self::countCallbacks($priorities);
        }
        foreach (self::$filters as $hook => $priorities) {
            $summary['filters'][$hook] = self::countCallbacks($priorities);
        }
        return $summary;
    }

    public static function reset(): void
    {
        self::$actions = [];
        self::$filters = [];
        self::$did = [];
        self::$current = [];
        self::$pluginContext = null;
    }

    /**
     * @param array<string,array<int,array<int,array{callback:callable,accepted:?int,plugin:?string}>>> $store
     * @param array<int,mixed> $args
     * @return mixed
     */
    private static function dispatch(array $store, string $hook, array $args, bool $isFilter, ?string $onlyPlugin = null)
    {
        $value = $isFilter ? ($args[0] ?? null) : null;
        $callArgs = $isFilter ? array_slice($args, 1) : $args;
        self::$current[] = $hook;
        self::$did[$hook] = (self::$did[$hook] ?? 0) + 1;

        try {
            if (empty($store[$hook])) {
                return $value;
            }
            foreach ($store[$hook] as $callbacks) {
                foreach ($callbacks as $entry) {
                    if ($onlyPlugin !== null && $entry['plugin'] !== $onlyPlugin) {
                        continue;
                    }
                    $callbackArgs = $isFilter ? array_merge([$value], $callArgs) : $callArgs;
                    if (is_int($entry['accepted'])) {
                        $callbackArgs = array_slice($callbackArgs, 0, $entry['accepted']);
                    }
                    try {
                        $result = call_user_func_array($entry['callback'], $callbackArgs);
                        if ($isFilter) {
                            $value = $result;
                        }
                    } catch (Throwable $e) {
                        Logger::exception($e, [
                            'hook' => $hook,
                            'plugin' => $entry['plugin'],
                        ]);
                    }
                }
            }
            return $value;
        } finally {
            array_pop(self::$current);
        }
    }

    /**
     * @param array<string,array<int,array<int,array{callback:callable,accepted:?int,plugin:?string}>>> $store
     */
    private static function removeFrom(array &$store, string $hook, callable $callback, ?int $priority): bool
    {
        if (empty($store[$hook])) {
            return false;
        }
        $removed = false;
        foreach ($store[$hook] as $p => $callbacks) {
            if ($priority !== null && $p !== $priority) {
                continue;
            }
            foreach ($callbacks as $index => $entry) {
                if ($entry['callback'] === $callback) {
                    unset($store[$hook][$p][$index]);
                    $removed = true;
                }
            }
            if (empty($store[$hook][$p])) {
                unset($store[$hook][$p]);
            }
        }
        if (empty($store[$hook])) {
            unset($store[$hook]);
        }
        return $removed;
    }

    private static function countCallbacks(array $priorities): array
    {
        $result = [];
        foreach ($priorities as $priority => $callbacks) {
            $result[$priority] = count($callbacks);
        }
        return $result;
    }
}
