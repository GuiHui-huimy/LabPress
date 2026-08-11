<?php
class LabPress_Hooks {
    private static $actions = [];
    private static $filters = [];

    public static function addAction($hook, $callback, $priority = 10) {
        if (!isset(self::$actions[$hook])) {
            self::$actions[$hook] = [];
        }
        if (!isset(self::$actions[$hook][$priority])) {
            self::$actions[$hook][$priority] = [];
        }
        self::$actions[$hook][$priority][] = $callback;
        // 对同一钩子的优先级排序
        ksort(self::$actions[$hook]);
    }

    public static function doAction($hook, ...$args) {
        if (empty(self::$actions[$hook])) return;
        foreach (self::$actions[$hook] as $priority => $callbacks) {
            foreach ($callbacks as $callback) {
                call_user_func_array($callback, $args);
            }
        }
    }

    public static function addFilter($hook, $callback, $priority = 10) {
        if (!isset(self::$filters[$hook])) {
            self::$filters[$hook] = [];
        }
        if (!isset(self::$filters[$hook][$priority])) {
            self::$filters[$hook][$priority] = [];
        }
        self::$filters[$hook][$priority][] = $callback;
        ksort(self::$filters[$hook]);
    }

    public static function applyFilters($hook, $value, ...$args) {
        if (empty(self::$filters[$hook])) return $value;
        foreach (self::$filters[$hook] as $priority => $callbacks) {
            foreach ($callbacks as $callback) {
                $value = call_user_func_array($callback, array_merge([$value], $args));
            }
        }
        return $value;
    }
}