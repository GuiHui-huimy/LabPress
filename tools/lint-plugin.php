<?php
declare(strict_types=1);

/**
 * Lint a plugin package before distribution.
 *
 * Usage:
 *   php tools/lint-plugin.php plugins/example
 *   php tools/lint-plugin.php plugins/example --strict-version-sync --strict-php-header
 */

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "CLI only.\n");
    exit(1);
}

$root = dirname(__DIR__);
define('LABPRESS_ROOT', $root);
require_once $root . '/core/Plugin/Manifest.php';

$args = array_slice($argv, 1);
$strictVersion = false;
$strictHeader = false;
$target = '';

foreach ($args as $arg) {
    if ($arg === '--strict-version-sync') {
        $strictVersion = true;
        continue;
    }
    if ($arg === '--strict-php-header') {
        $strictHeader = true;
        continue;
    }
    if ($target === '' && !str_starts_with($arg, '--')) {
        $target = $arg;
    }
}

if ($target === '') {
    fwrite(STDERR, "Usage: php tools/lint-plugin.php <plugin-directory> [--strict-version-sync] [--strict-php-header]\n");
    exit(2);
}

$directory = str_starts_with($target, '/') ? $target : $root . '/' . ltrim($target, '/');
if (!is_dir($directory)) {
    fwrite(STDERR, "Plugin directory not found: {$target}\n");
    exit(2);
}

$slug = basename($directory);
$pluginFile = $directory . '/plugin.php';
$header = is_file($pluginFile) ? parsePluginHeader($pluginFile) : [];

if (is_file($directory . '/plugin.json')) {
    $result = \LabPress\Plugin\Manifest::fromDirectory($directory, false);
} else {
    $result = [
        'ok' => true,
        'manifest' => \LabPress\Plugin\Manifest::normalize([
            'slug' => $slug,
            'name' => $header['Plugin Name'] ?? $slug,
            'version' => $header['Version'] ?? '0.0.0',
            'description' => $header['Description'] ?? 'Legacy plugin',
            'author' => $header['Author'] ?? 'Unknown',
            'requires' => ['core' => $header['Requires LabPress'] ?? ''],
        ], $slug, true),
        'legacy' => true,
        'errors' => [],
        'file' => $pluginFile,
    ];
}

$errors = $result['errors'];
if (!empty($result['legacy'])) {
    $errors = array_merge($errors, \LabPress\Plugin\Manifest::validate($result['manifest']));
}

if (!is_file($pluginFile)) {
    $errors[] = '缺少 plugin.php。';
} else {
    if ($strictHeader) {
        foreach (['Plugin Name', 'Description', 'Version', 'Author'] as $field) {
            if (trim((string)($header[$field] ?? '')) === '') {
                $errors[] = 'plugin.php 缺少头字段：' . $field;
            }
        }
        $requires = trim((string)($header['Requires LabPress'] ?? ''));
        if ($requires === '') {
            $errors[] = 'plugin.php 缺少头字段：Requires LabPress';
        }
    }
    if ($strictVersion && is_file($directory . '/plugin.json')) {
        $manifestVersion = (string)($result['manifest']['version'] ?? '');
        $headerVersion = trim((string)($header['Version'] ?? ''));
        if ($manifestVersion !== '' && $headerVersion !== '' && $manifestVersion !== $headerVersion) {
            $errors[] = "版本不同步：plugin.json={$manifestVersion}，plugin.php={$headerVersion}";
        }
    }
    if (!empty($result['manifest']['legacy'])) {
        $headerSlug = trim((string)($header['Plugin Slug'] ?? ''));
        if ($headerSlug !== '' && $headerSlug !== $slug) {
            $errors[] = "旧头注释 slug 与目录名不一致：{$headerSlug} / {$slug}";
        }
    }
}

if ($errors !== []) {
    fwrite(STDERR, $slug . ': FAIL（' . count($errors) . " 项）\n");
    foreach ($errors as $error) {
        fwrite(STDERR, ' - ' . $error . "\n");
    }
    exit(1);
}

$mode = !empty($result['manifest']['legacy']) ? 'legacy header' : 'manifest v2';
echo $slug . ': OK（' . $mode . "）\n";

/**
 * @return array<string,string>
 */
function parsePluginHeader(string $file): array
{
    $content = (string)@file_get_contents($file, false, null, 0, 8192);
    if (!preg_match('/\/\*\*(.*?)\*\//s', $content, $block)) {
        return [];
    }
    $header = [];
    foreach (preg_split('/\r\n|\n|\r/', $block[1]) ?: [] as $line) {
        if (preg_match('/^\s*\*?\s*([A-Za-z][A-Za-z0-9 _-]{1,40}):\s*(.*?)\s*$/u', $line, $match)) {
            $header[trim($match[1])] = trim($match[2]);
        }
    }
    return $header;
}
