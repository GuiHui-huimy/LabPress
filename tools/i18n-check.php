<?php
declare(strict_types=1);

/**
 * Lightweight translation coverage check for CI.
 *
 * It only inspects literal __() / _e() calls and compares them with the
 * selected locale catalogs. Dynamic keys are intentionally skipped.
 */

$root = dirname(__DIR__);
$locales = ['zh_CN', 'en_US', 'ja_JP'];
$coreModules = ['common', 'admin', 'index', 'news', 'projects', 'publications', 'footer'];
$keys = [];
$missing = [];
$catalogs = [];

$scanDirs = ['core', 'includes', 'admin', 'api', 'plugins', 'bin', 'install'];
foreach ($scanDirs as $dir) {
    $path = $root . '/' . $dir;
    if (!is_dir($path)) {
        continue;
    }
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS));
    foreach ($iterator as $file) {
        if (!$file->isFile() || $file->getExtension() !== 'php') {
            continue;
        }
        $content = (string)file_get_contents($file->getPathname());
        preg_match_all('/\b(?:__|_e)\(\s*([\'"])(.*?)\1\s*,\s*([\'"])(.*?)\3/u', $content, $matches, PREG_SET_ORDER);
        foreach ($matches as $match) {
            $key = $match[2];
            $module = $match[4];
            if ($key === '' || $module === '' || str_contains($key, '$')) {
                continue;
            }
            if (str_starts_with($module, 'plugin:')) {
                $parts = explode(':', $module, 3);
                $slug = $parts[1] ?? '';
                $domain = $parts[2] ?? 'plugin';
                if ($slug === '') {
                    continue;
                }
                $keys[$module][$key] = true;
                $catalogKey = $module . '|' . $domain;
                foreach ($locales as $locale) {
                    $file = $root . '/plugins/' . $slug . '/languages/' . $locale . '/' . $domain . '.php';
                    if (!is_file($file)) {
                        $file = $root . '/plugins/' . $slug . '/languages/' . $locale . '.php';
                    }
                    if (!is_file($file)) {
                        continue;
                    }
                    $catalogs[$catalogKey][$locale] = array_replace(
                        $catalogs[$catalogKey][$locale] ?? [],
                        (array)(include $file)
                    );
                }
            } elseif (in_array($module, $coreModules, true)) {
                $keys[$module][$key] = true;
                foreach ($locales as $locale) {
                    $file = $root . '/languages/' . $locale . '/' . $module . '.php';
                    if (is_file($file)) {
                        $catalogs[$module][$locale] = array_replace(
                            $catalogs[$module][$locale] ?? [],
                            (array)(include $file)
                        );
                    }
                }
            }
        }
    }
}

// Locale metadata must be valid and point to a locale directory.
foreach ($locales as $locale) {
    $metaFile = $root . '/languages/' . $locale . '/locale.json';
    if (is_file($metaFile)) {
        $meta = json_decode((string)file_get_contents($metaFile), true);
        if (!is_array($meta) || empty($meta['locale'])) {
            $missing[] = $locale . ': locale.json 无效';
        }
    }
}

foreach ($keys as $module => $moduleKeys) {
    foreach ($moduleKeys as $key => $_) {
        foreach ($locales as $locale) {
            if ($locale === 'ja_JP' && !isset($catalogs[$module][$locale])) {
                continue; // ja_JP uses the en_US fallback until a full catalog lands.
            }
            $catalogKey = str_starts_with($module, 'plugin:')
                ? $module . '|' . (explode(':', $module, 3)[2] ?? 'plugin')
                : $module;
            if (!isset($catalogs[$catalogKey][$locale][$key])) {
                $missing[] = $locale . ' ' . $module . ':' . $key;
            }
        }
    }
}

$checked = 0;
foreach ($keys as $moduleKeys) {
    $checked += count($moduleKeys);
}
if ($missing !== []) {
    fwrite(STDERR, "i18n check failed: missing " . count($missing) . " translation(s)\n");
    foreach (array_slice($missing, 0, 80) as $item) {
        fwrite(STDERR, " - " . $item . "\n");
    }
    exit(1);
}
echo "i18n OK (" . $checked . " keys, " . count($locales) . " locales)\n";
