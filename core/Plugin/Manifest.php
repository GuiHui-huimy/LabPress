<?php
declare(strict_types=1);

namespace LabPress\Plugin;

/**
 * Plugin Manifest v2 reader and validator.
 *
 * New packages declare `plugin.json`; legacy packages keep the old plugin.php
 * header. Both forms are normalized into the same array so the rest of the core
 * does not need to know which one is in use.
 */
final class Manifest
{
    public const SCHEMA_VERSION = 2;
    public const LEGACY_SCHEMA_VERSION = 1;

    /**
     * @return array{ok:bool,manifest:array<string,mixed>,legacy:bool,errors:array<int,string>,file:?string}
     */
    public static function fromDirectory(string $directory, bool $allowLegacy = true): array
    {
        $directory = rtrim($directory, DIRECTORY_SEPARATOR);
        $slug = basename($directory);
        $jsonFile = $directory . '/plugin.json';
        $pluginFile = $directory . '/plugin.php';

        if (is_file($jsonFile)) {
            $raw = @file_get_contents($jsonFile);
            $decoded = is_string($raw) ? json_decode($raw, true) : null;
            if (!is_array($decoded)) {
                return self::failure($slug, 'plugin.json 不是合法 JSON。', $jsonFile);
            }

            $manifest = self::normalize($decoded, $slug, false);
            $errors = self::validate($manifest);
            return [
                'ok' => $errors === [],
                'manifest' => $manifest,
                'legacy' => false,
                'errors' => $errors,
                'file' => $jsonFile,
            ];
        }

        if (!$allowLegacy || !is_file($pluginFile)) {
            return self::failure($slug, '未找到 plugin.json，且缺少 plugin.php。', null);
        }

        $header = Manager::parseHeader($pluginFile);
        $manifest = self::normalize([
            'slug' => $slug,
            'name' => $header['name'] !== '' ? $header['name'] : $slug,
            'version' => $header['version'] !== '' ? $header['version'] : '0.0.0',
            'description' => $header['description'] !== '' ? $header['description'] : 'Legacy plugin',
            'author' => $header['author'] !== '' ? $header['author'] : 'Unknown',
            'license' => 'GPL-3.0-or-later',
            'requires' => [
                'core' => $header['requires_core'],
            ],
            'provides' => [$slug],
            'settings' => [],
        ], $slug, true);

        return [
            'ok' => true,
            'manifest' => $manifest,
            'legacy' => true,
            'errors' => [],
            'file' => $pluginFile,
        ];
    }

    /**
     * Normalize a manifest and fill defaults. Validation is intentionally
     * separate so callers can display errors on an otherwise usable record.
     *
     * @param array<string,mixed> $input
     * @return array<string,mixed>
     */
    public static function normalize(array $input, string $fallbackSlug = '', bool $legacy = false): array
    {
        $slug = trim((string)($input['slug'] ?? $fallbackSlug));
        $requires = is_array($input['requires'] ?? null) ? $input['requires'] : [];
        $provides = is_array($input['provides'] ?? null) ? array_values($input['provides']) : [$slug];
        $conflicts = is_array($input['conflicts'] ?? null) ? array_values($input['conflicts']) : [];
        $settings = is_array($input['settings'] ?? null) ? array_values($input['settings']) : [];
        $adminPages = is_array($input['admin_pages'] ?? null) ? array_values($input['admin_pages']) : [];

        return [
            'schema_version' => $legacy ? self::LEGACY_SCHEMA_VERSION : self::SCHEMA_VERSION,
            'slug' => $slug,
            'name' => trim((string)($input['name'] ?? $slug)),
            'version' => trim((string)($input['version'] ?? '0.0.0')),
            'description' => trim((string)($input['description'] ?? '')),
            'author' => trim((string)($input['author'] ?? '')),
            'license' => trim((string)($input['license'] ?? ($legacy ? 'GPL-3.0-or-later' : ''))),
            'requires' => [
                'core' => trim((string)($requires['core'] ?? '')),
                'php' => trim((string)($requires['php'] ?? '')),
                'plugins' => is_array($requires['plugins'] ?? null) ? $requires['plugins'] : [],
            ],
            'provides' => array_values(array_filter(array_map('strval', $provides), static fn(string $v): bool => $v !== '')),
            'conflicts' => array_values(array_filter(array_map('strval', $conflicts), static fn(string $v): bool => $v !== '')),
            'settings' => $settings,
            'admin_pages' => $adminPages,
            'legacy' => $legacy,
        ];
    }

    /**
     * @param array<string,mixed> $manifest
     * @return array<int,string>
     */
    public static function validate(array $manifest): array
    {
        $errors = [];
        $legacy = !empty($manifest['legacy']);
        $slug = (string)($manifest['slug'] ?? '');

        if ($slug === '') {
            $errors[] = 'slug 不能为空。';
        } elseif (!$legacy && !preg_match('/^[a-z0-9][a-z0-9\-]{1,63}$/', $slug)) {
            $errors[] = 'slug 必须为 2–64 位小写字母、数字或连字符。';
        } elseif (!preg_match('/^[A-Za-z0-9][A-Za-z0-9_.-]{0,63}$/', $slug)) {
            $errors[] = 'slug 格式不正确。';
        }

        if (trim((string)($manifest['name'] ?? '')) === '') {
            $errors[] = 'name 不能为空。';
        } elseif (mb_strlen((string)$manifest['name']) > 120) {
            $errors[] = 'name 超过 120 个字符。';
        }

        if (!preg_match('/^[0-9]+\.[0-9]+\.[0-9]+(?:[-.+][0-9A-Za-z.-]+)?$/', (string)($manifest['version'] ?? ''))) {
            $errors[] = 'version 必须是语义化版本，例如 1.2.3。';
        }

        if (trim((string)($manifest['description'] ?? '')) === '') {
            $errors[] = 'description 不能为空。';
        } elseif (mb_strlen((string)$manifest['description']) > 2000) {
            $errors[] = 'description 超过 2000 个字符。';
        }

        if (trim((string)($manifest['author'] ?? '')) === '') {
            $errors[] = 'author 不能为空。';
        }

        if (trim((string)($manifest['license'] ?? '')) === '') {
            $errors[] = 'license 不能为空。';
        } elseif (!preg_match('/^[A-Za-z0-9.+\- ]{1,64}$/', (string)$manifest['license'])) {
            $errors[] = 'license 格式不正确。';
        }

        $requires = is_array($manifest['requires'] ?? null) ? $manifest['requires'] : [];
        foreach (['core', 'php'] as $key) {
            $constraint = (string)($requires[$key] ?? '');
            if ($constraint !== '' && !self::validConstraint($constraint)) {
                $errors[] = 'requires.' . $key . ' 约束格式不正确。';
            }
        }
        if (!is_array($requires['plugins'] ?? [])) {
            $errors[] = 'requires.plugins 必须是对象。';
        } else {
            foreach ($requires['plugins'] as $dependency => $constraint) {
                if (!is_string($dependency) || $dependency === '' || !self::validConstraint((string)$constraint)) {
                    $errors[] = 'requires.plugins 中的依赖名称或版本约束不正确。';
                    break;
                }
            }
        }

        foreach (['provides', 'conflicts'] as $key) {
            if (!is_array($manifest[$key] ?? null)) {
                $errors[] = $key . ' 必须是数组。';
                continue;
            }
            foreach ($manifest[$key] as $value) {
                if (!is_string($value) || $value === '') {
                    $errors[] = $key . ' 不能包含空值。';
                    break;
                }
            }
        }

        if (!is_array($manifest['settings'] ?? null)) {
            $errors[] = 'settings 必须是数组。';
        } else {
            $settingsErrors = self::validateSettings($manifest['settings']);
            if ($settingsErrors !== []) {
                $errors = array_merge($errors, $settingsErrors);
            }
        }

        return $errors;
    }

    /**
     * @param array<int,array<string,mixed>> $settings
     * @return array<int,string>
     */
    private static function validateSettings(array $settings): array
    {
        $errors = [];
        $allowedTypes = ['text', 'textarea', 'select', 'checkbox', 'number', 'color', 'url', 'email'];
        $keys = [];
        foreach ($settings as $index => $field) {
            if (!is_array($field)) {
                $errors[] = 'settings[' . $index . '] 必须是对象。';
                continue;
            }
            $key = (string)($field['key'] ?? '');
            $type = (string)($field['type'] ?? 'text');
            if (!preg_match('/^[a-z][a-z0-9_.-]{1,63}$/', $key)) {
                $errors[] = 'settings[' . $index . '].key 格式不正确。';
            }
            if (isset($keys[$key])) {
                $errors[] = 'settings 中存在重复 key：' . $key;
            }
            $keys[$key] = true;
            if (!in_array($type, $allowedTypes, true)) {
                $errors[] = 'settings[' . $index . '].type 不支持：' . $type;
            }
            if (trim((string)($field['label'] ?? '')) === '') {
                $errors[] = 'settings[' . $index . '].label 不能为空。';
            }
            if ($type === 'select') {
                if (!is_array($field['options'] ?? null) || $field['options'] === []) {
                    $errors[] = 'settings[' . $index . '].options 不能为空。';
                }
            }
        }
        return $errors;
    }

    public static function validConstraint(string $constraint): bool
    {
        $constraint = trim($constraint);
        if ($constraint === '' || $constraint === '*' || $constraint === 'any') {
            return true;
        }
        if (!preg_match('/^(>=|<=|>|<|==|=|\^|~)?\s*v?[0-9xX*][0-9A-Za-z.*\-]*$/u', $constraint)) {
            return false;
        }
        return true;
    }

    /**
     * @return array{ok:bool,manifest:array<string,mixed>,legacy:bool,errors:array<int,string>,file:?string}
     */
    private static function failure(string $slug, string $error, ?string $file): array
    {
        return [
            'ok' => false,
            'manifest' => self::normalize(['slug' => $slug], $slug, false),
            'legacy' => false,
            'errors' => [$error],
            'file' => $file,
        ];
    }
}
