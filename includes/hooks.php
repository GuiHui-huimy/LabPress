<?php
/**
 * 兼容包装：旧插件继续使用 LabPress_Hooks，实际实现是 v1.2.0 Hook Engine。
 */
require_once __DIR__ . '/bootstrap.php';

class LabPress_Hooks
{
    public static function addAction($hook, $callback, $priority = 10, $acceptedArgs = null)
    {
        \LabPress\Hooks::addAction((string)$hook, $callback, (int)$priority, $acceptedArgs === null ? null : (int)$acceptedArgs);
    }

    public static function doAction($hook, ...$args)
    {
        \LabPress\Hooks::doAction((string)$hook, ...$args);
    }

    public static function addFilter($hook, $callback, $priority = 10, $acceptedArgs = null)
    {
        \LabPress\Hooks::addFilter((string)$hook, $callback, (int)$priority, $acceptedArgs === null ? null : (int)$acceptedArgs);
    }

    public static function applyFilters($hook, $value, ...$args)
    {
        return \LabPress\Hooks::applyFilters((string)$hook, $value, ...$args);
    }

    public static function removeAction($hook, $callback, $priority = null)
    {
        return \LabPress\Hooks::removeAction((string)$hook, $callback, $priority === null ? null : (int)$priority);
    }

    public static function removeFilter($hook, $callback, $priority = null)
    {
        return \LabPress\Hooks::removeFilter((string)$hook, $callback, $priority === null ? null : (int)$priority);
    }

    public static function hasAction($hook)
    {
        return \LabPress\Hooks::hasAction((string)$hook);
    }

    public static function hasFilter($hook)
    {
        return \LabPress\Hooks::hasFilter((string)$hook);
    }

    public static function didAction($hook)
    {
        return \LabPress\Hooks::didAction((string)$hook);
    }

    public static function currentHook()
    {
        return \LabPress\Hooks::currentHook();
    }

    public static function collect()
    {
        return \LabPress\Hooks::collect();
    }

    public static function reset()
    {
        \LabPress\Hooks::reset();
    }
}
