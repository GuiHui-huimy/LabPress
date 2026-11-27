<?php
declare(strict_types=1);

namespace LabPress\Plugin;

use LabPress\Admin\Registry;
use LabPress\Guard;
use PDO;

/**
 * Plugin Settings API v1.3.0.
 *
 * A plugin declares fields in plugin.json (or passes the same array to
 * Settings::register()), then calls Settings::register() during plugin load.
 * The core renders the form, validates known fields and stores values in
 * plugins.settings_json.
 */
final class Settings
{
    /** @var array<string,array<int,array<string,mixed>>> */
    private static array $schemas = [];
    /** @var array<string,bool> */
    private static array $registered = [];
    /** @var array<string,string> */
    private static array $pageSlugs = [];
    /** @var array<string,string> */
    private static array $pageModules = [];

    /**
     * @param array<int,array<string,mixed>> $schema
     */
    public static function register(string $slug, array $schema, string $title = '', string $permission = 'all', string $pageSlug = '', string $titleModule = ''): void
    {
        $slug = self::normalizeSlug($slug);
        if ($slug === '') {
            return;
        }
        self::$schemas[$slug] = array_values($schema);
        if ($title === '' || isset(self::$registered[$slug])) {
            return;
        }
        $pageSlug = $pageSlug !== '' ? preg_replace('/[^a-zA-Z0-9_-]/', '', $pageSlug) : 'plugin-settings-' . $slug;
        if ($pageSlug === '') {
            $pageSlug = 'plugin-settings-' . $slug;
        }
        Registry::register(
            $pageSlug,
            $title,
            static function () use ($slug): void {
                self::renderPage($slug);
            },
            $permission,
            $permission
        );
        self::$pageSlugs[$slug] = $pageSlug;
        if ($titleModule !== '') {
            self::$pageModules[$slug] = $titleModule;
        }
        self::$registered[$slug] = true;
    }

    public static function get(string $slug, string $key, mixed $default = null): mixed
    {
        $values = self::all($slug);
        return array_key_exists($key, $values) ? $values[$key] : $default;
    }

    public static function hasSchema(string $slug): bool
    {
        return isset(self::$schemas[self::normalizeSlug($slug)]);
    }

    /**
     * @return array<string,mixed>
     */
    public static function all(string $slug): array
    {
        $slug = self::normalizeSlug($slug);
        $defaults = [];
        foreach (self::$schemas[$slug] ?? [] as $field) {
            $key = (string)($field['key'] ?? '');
            if ($key !== '') {
                $defaults[$key] = $field['default'] ?? '';
            }
        }
        $saved = self::read($slug);
        return array_replace($defaults, $saved);
    }

    /**
     * Return only values persisted in plugins.settings_json, without defaults.
     *
     * @return array<string,mixed>
     */
    public static function saved(string $slug): array
    {
        return self::read(self::normalizeSlug($slug));
    }

    /**
     * @param array<string,mixed> $input
     * @return array{ok:bool,errors:array<int,string>,values?:array<string,mixed>}
     */
    public static function save(string $slug, array $input): array
    {
        $slug = self::normalizeSlug($slug);
        $schema = self::$schemas[$slug] ?? [];
        if ($slug === '' || $schema === []) {
            return ['ok' => false, 'errors' => ['设置 schema 未注册。']];
        }

        $errors = [];
        $values = [];
        foreach ($schema as $field) {
            $key = (string)($field['key'] ?? '');
            if ($key === '') {
                continue;
            }
            $type = (string)($field['type'] ?? 'text');
            $raw = $input[$key] ?? ($field['default'] ?? '');
            if ($type === 'checkbox') {
                $values[$key] = in_array((string)$raw, ['1', 'true', 'on', 'yes'], true) ? 1 : 0;
                continue;
            }
            if (is_array($raw)) {
                $errors[] = $key . ' 的值类型不正确。';
                continue;
            }
            $value = trim((string)$raw);
            if (!empty($field['required']) && $value === '') {
                $errors[] = self::fieldLabel($field) . ' 不能为空。';
                continue;
            }
            if (isset($field['maxlength']) && mb_strlen($value) > (int)$field['maxlength']) {
                $errors[] = self::fieldLabel($field) . ' 超过长度限制。';
                continue;
            }
            if ($type === 'number' && $value !== '' && !is_numeric($value)) {
                $errors[] = self::fieldLabel($field) . ' 必须是数字。';
                continue;
            }
            if ($type === 'select') {
                $options = is_array($field['options'] ?? null) ? $field['options'] : [];
                if (!array_key_exists($value, $options)) {
                    $errors[] = self::fieldLabel($field) . ' 不是有效选项。';
                    continue;
                }
            }
            if (in_array($type, ['url', 'email'], true) && $value !== '') {
                $filter = $type === 'url' ? FILTER_VALIDATE_URL : FILTER_VALIDATE_EMAIL;
                if (filter_var($value, $filter) === false) {
                    $errors[] = self::fieldLabel($field) . ' 格式不正确。';
                    continue;
                }
                if ($type === 'url' && !preg_match('#^https?://#i', $value)) {
                    $errors[] = self::fieldLabel($field) . ' 必须以 http(s):// 开头。';
                    continue;
                }
            }
            $values[$key] = $value;
        }

        if ($errors !== []) {
            return ['ok' => false, 'errors' => $errors];
        }

        $stmt = self::db()->prepare('UPDATE plugins SET settings_json = ? WHERE slug = ?');
        $stmt->execute([json_encode($values, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), $slug]);
        if ($stmt->rowCount() === 0) {
            $check = self::db()->prepare('SELECT COUNT(*) FROM plugins WHERE slug = ?');
            $check->execute([$slug]);
            if ((int)$check->fetchColumn() === 0) {
                return ['ok' => false, 'errors' => ['插件不存在：' . $slug]];
            }
        }

        return ['ok' => true, 'errors' => [], 'values' => $values];
    }

    /**
     * @return array<string,mixed>
     */
    private static function read(string $slug): array
    {
        try {
            $stmt = self::db()->prepare('SELECT settings_json FROM plugins WHERE slug = ? LIMIT 1');
            $stmt->execute([$slug]);
            $raw = $stmt->fetchColumn();
            if (!is_string($raw) || $raw === '') {
                return [];
            }
            $decoded = json_decode($raw, true);
            return is_array($decoded) ? $decoded : [];
        } catch (\Throwable) {
            return [];
        }
    }

    private static function renderPage(string $slug): void
    {
        if (!Guard::isLoggedIn()) {
            echo '<div class="card"><p>请先登录。</p></div>';
            return;
        }

        $schema = self::$schemas[$slug] ?? [];
        if ($schema === []) {
            echo '<div class="card"><p>该插件尚未注册设置 schema。</p></div>';
            return;
        }

        $message = '';
        $error = '';
        if (Guard::isWriteRequest()) {
            Guard::guardWriteRequest();
            $input = $_POST['settings'] ?? [];
            if (!is_array($input)) {
                $error = '设置数据格式不正确。';
            } else {
                $result = self::save($slug, $input);
                if ($result['ok']) {
                    $message = '设置已保存。';
                } else {
                    $error = implode(' ', $result['errors']);
                }
            }
        }

        $values = self::all($slug);
        $e = static fn(mixed $value): string => htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        echo '<div class="card">';
        echo '<h2>' . $e(self::pageTitle($slug)) . '</h2>';
        if ($message !== '') {
            echo '<p class="alert alert-success">' . $e($message) . '</p>';
        }
        if ($error !== '') {
            echo '<p class="alert alert-error">' . $e($error) . '</p>';
        }
        echo '<form method="post" action="">';
        echo '<input type="hidden" name="_csrf" value="' . $e(Guard::csrfToken()) . '">';
        foreach ($schema as $field) {
            $key = (string)($field['key'] ?? '');
            if ($key === '') {
                continue;
            }
            $type = (string)($field['type'] ?? 'text');
            $label = self::fieldLabel($field);
            $value = (string)($values[$key] ?? '');
            $required = !empty($field['required']) ? ' required' : '';
            echo '<p><label for="lp-setting-' . $e($key) . '">' . $e($label) . '</label><br>';
            if ($type === 'textarea') {
                echo '<textarea id="lp-setting-' . $e($key) . '" name="settings[' . $e($key) . ']"' . $required . '>' . $e($value) . '</textarea>';
            } elseif ($type === 'select') {
                echo '<select id="lp-setting-' . $e($key) . '" name="settings[' . $e($key) . ']"' . $required . '>';
                foreach ((array)($field['options'] ?? []) as $optionValue => $optionLabel) {
                    $selected = (string)$optionValue === $value ? ' selected' : '';
                    echo '<option value="' . $e($optionValue) . '"' . $selected . '>' . $e(self::optionLabel($field, (string)$optionLabel)) . '</option>';
                }
                echo '</select>';
            } elseif ($type === 'checkbox') {
                echo '<input type="hidden" name="settings[' . $e($key) . ']" value="0">';
                echo '<input type="checkbox" id="lp-setting-' . $e($key) . '" name="settings[' . $e($key) . ']" value="1"' . ((string)$value === '1' ? ' checked' : '') . '>';
            } else {
                $inputType = in_array($type, ['number', 'color', 'url', 'email'], true) ? $type : 'text';
                echo '<input type="' . $inputType . '" id="lp-setting-' . $e($key) . '" name="settings[' . $e($key) . ']" value="' . $e($value) . '"' . $required . '>';
            }
            if (!empty($field['help'])) {
                echo '<br><small>' . $e($field['help']) . '</small>';
            }
            echo '</p>';
        }
        echo '<p><button type="submit" class="btn btn-primary">保存设置</button></p>';
        echo '</form></div>';
    }

    private static function pageTitle(string $slug): string
    {
        $pageSlug = self::$pageSlugs[$slug] ?? ('plugin-settings-' . $slug);
        $page = Registry::get($pageSlug);
        $title = (string)($page['title'] ?? ('插件设置：' . $slug));
        $module = self::$pageModules[$slug] ?? '';
        return $module !== '' && function_exists('__') ? (string)__($title, $module) : $title;
    }

    /**
     * @param array<string,mixed> $field
     */
    private static function fieldLabel(array $field): string
    {
        $label = (string)($field['label'] ?? $field['key'] ?? '');
        $module = (string)($field['label_module'] ?? '');
        return $module !== '' && function_exists('__') ? (string)__($label, $module) : $label;
    }

    /**
     * @param array<string,mixed> $field
     */
    private static function optionLabel(array $field, string $label): string
    {
        $module = (string)($field['options_module'] ?? $field['label_module'] ?? '');
        return $module !== '' && function_exists('__') ? (string)__($label, $module) : $label;
    }

    private static function normalizeSlug(string $slug): string
    {
        return preg_replace('/[^A-Za-z0-9_-]/', '', $slug) ?? '';
    }

    private static function db(): PDO
    {
        global $pdo;
        if (!$pdo instanceof PDO) {
            throw new \RuntimeException('Database is not bootstrapped');
        }
        return $pdo;
    }
}
