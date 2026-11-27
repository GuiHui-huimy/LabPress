<?php
declare(strict_types=1);

namespace LabPress\Plugin;

/**
 * Safe ZIP package installer for LabPress plugins.
 *
 * The flow is: inspect every ZIP entry without extracting, stage into
 * storage/tmp, validate plugin.json/legacy header, then atomically swap the
 * plugin directory. A failed swap restores the previous directory.
 */
final class PackageInstaller
{
    private const MAX_ZIP_BYTES = 20 * 1024 * 1024;
    private const MAX_ENTRIES = 2000;
    private const MAX_UNPACKED_BYTES = 50 * 1024 * 1024;
    private const MAX_COMPRESSION_RATIO = 100;

    private const DANGEROUS_EXTENSIONS = [
        'phar', 'so', 'dll', 'exe', 'sh', 'bat', 'cmd', 'ps1', 'msi',
        'com', 'scr', 'phtml', 'htaccess', 'env', 'pem', 'key', 'p12', 'pfx',
    ];

    private string $appRoot;

    public function __construct(string $appRoot)
    {
        $this->appRoot = rtrim($appRoot, DIRECTORY_SEPARATOR);
    }

    /**
     * @return array{ok:bool,error?:string,slug?:string,manifest?:array<string,mixed>,legacy?:bool,mode?:string}
     */
    public function install(string $zipPath): array
    {
        $zipPath = (string)$zipPath;
        if (!is_file($zipPath)) {
            return $this->fail('ZIP 文件不存在。');
        }
        if (filesize($zipPath) > self::MAX_ZIP_BYTES) {
            return $this->fail('插件包不能超过 20MB。');
        }
        if (!class_exists(\ZipArchive::class)) {
            return $this->fail('服务器未安装 ZIP 扩展。');
        }

        $zip = new \ZipArchive();
        if ($zip->open($zipPath) !== true) {
            return $this->fail('无法打开 ZIP 文件。');
        }

        $staging = '';
        try {
            $inspection = $this->inspect($zip);
            if (!$inspection['ok']) {
                return $this->fail((string)$inspection['error']);
            }

            $staging = $this->createStagingDirectory();
            if ($staging === '') {
                return $this->fail('无法创建临时安装目录。');
            }

            $extract = $this->extract($zip, $inspection['entries'], (string)$inspection['root'], $staging);
            if (!$extract['ok']) {
                return $this->fail((string)$extract['error']);
            }

            $manifestResult = Manifest::fromDirectory($staging, true);
            if (!$manifestResult['ok']) {
                return $this->fail(implode(' ', $manifestResult['errors']));
            }
            $manifest = $manifestResult['manifest'];
            if (!is_file($staging . '/plugin.php')) {
                return $this->fail('插件包缺少 plugin.php。');
            }

            $slug = $this->safeSlug((string)$manifest['slug']);
            if ($slug === '') {
                return $this->fail('插件 slug 不合法。');
            }

            $pluginsDir = $this->appRoot . '/plugins';
            if (!is_dir($pluginsDir) || !is_writable($pluginsDir)) {
                return $this->fail('插件目录不可写。');
            }

            $target = $pluginsDir . '/' . $slug;
            if (is_link($target)) {
                return $this->fail('目标插件目录是符号链接，拒绝覆盖。');
            }
            if (file_exists($target) && !is_dir($target)) {
                return $this->fail('目标路径已存在且不是目录。');
            }

            $mode = is_dir($target) ? 'update' : 'install';
            $backup = null;
            if ($mode === 'update') {
                $backup = $target . '.backup-' . date('YmdHis') . '-' . bin2hex(random_bytes(4));
                if (!rename($target, $backup)) {
                    return $this->fail('无法备份旧插件目录。');
                }
            }

            if (!rename($staging, $target)) {
                if ($backup !== null && is_dir($backup)) {
                    @rename($backup, $target);
                }
                return $this->fail('无法把插件移动到 plugins 目录。');
            }
            $staging = '';

            if ($backup !== null && is_dir($backup)) {
                $this->removeDirectory($backup);
            }

            $this->log([
                'action' => $mode,
                'slug' => $slug,
                'version' => (string)$manifest['version'],
                'legacy' => (bool)$manifestResult['legacy'],
                'result' => 'ok',
            ]);

            return [
                'ok' => true,
                'slug' => $slug,
                'manifest' => $manifest,
                'legacy' => (bool)$manifestResult['legacy'],
                'mode' => $mode,
            ];
        } finally {
            $zip->close();
            if ($staging !== '') {
                $this->removeDirectory($staging);
            }
        }
    }

    /**
     * @return array{ok:bool,error?:string,entries?:array<int,array{name:string,index:int,size:int}>,root?:string}
     */
    private function inspect(\ZipArchive $zip): array
    {
        if ($zip->numFiles <= 0) {
            return ['ok' => false, 'error' => 'ZIP 文件为空。'];
        }
        if ($zip->numFiles > self::MAX_ENTRIES) {
            return ['ok' => false, 'error' => '压缩包条目过多。'];
        }

        $entries = [];
        $total = 0;
        $topLevels = [];
        $hasRootManifest = false;

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $stat = $zip->statIndex($i);
            if ($stat === false) {
                return ['ok' => false, 'error' => '无法读取 ZIP 条目。'];
            }
            $name = str_replace('\\', '/', (string)$stat['name']);
            if ($name === '' || $name === '/' || str_contains($name, "\0")) {
                return ['ok' => false, 'error' => 'ZIP 包含非法路径。'];
            }
            if (str_starts_with($name, '/') || preg_match('/^[A-Za-z]:\//', $name) === 1) {
                return ['ok' => false, 'error' => 'ZIP 不允许绝对路径。'];
            }

            $segments = explode('/', rtrim($name, '/'));
            foreach ($segments as $segment) {
                if ($segment === '..') {
                    return ['ok' => false, 'error' => 'ZIP 包含路径穿越条目。'];
                }
            }

            $isDir = str_ends_with($name, '/');
            if ($isDir) {
                continue;
            }

            $size = (int)($stat['size'] ?? 0);
            $compressed = (int)($stat['comp_size'] ?? 0);
            $total += $size;
            if ($total > self::MAX_UNPACKED_BYTES) {
                return ['ok' => false, 'error' => '解压后总大小超过 50MB。'];
            }
            if ($compressed > 0 && $size / $compressed > self::MAX_COMPRESSION_RATIO) {
                return ['ok' => false, 'error' => '存在压缩比异常的文件。'];
            }

            $extension = strtolower(pathinfo($name, PATHINFO_EXTENSION));
            if (in_array($extension, self::DANGEROUS_EXTENSIONS, true)) {
                return ['ok' => false, 'error' => '插件包包含不允许的文件类型：.' . $extension];
            }

            $opsys = 0;
            $attributes = 0;
            if ($zip->getExternalAttributesIndex($i, $opsys, $attributes)) {
                $mode = ($attributes >> 16) & 0xFFFF;
                if (($mode & 0xF000) === 0xA000) {
                    return ['ok' => false, 'error' => '插件包不允许符号链接。'];
                }
            }

            if ($name === 'plugin.json') {
                $hasRootManifest = true;
            }
            $entries[] = ['name' => $name, 'index' => $i, 'size' => $size];
            $topLevels[] = $segments[0];
        }

        if ($entries === []) {
            return ['ok' => false, 'error' => 'ZIP 文件没有可用文件。'];
        }

        $root = '';
        if (!$hasRootManifest) {
            $top = array_values(array_unique($topLevels));
            if (count($top) !== 1) {
                return ['ok' => false, 'error' => '插件包必须只有一个根目录。'];
            }
            $root = (string)$top[0];
        }

        if ($root !== '') {
            foreach ($entries as $entry) {
                if (!str_starts_with($entry['name'], $root . '/')) {
                    return ['ok' => false, 'error' => '插件包必须只有一个根目录。'];
                }
            }
        }

        return ['ok' => true, 'entries' => $entries, 'root' => $root];
    }

    /**
     * @param array<int,array{name:string,index:int,size:int}> $entries
     * @return array{ok:bool,error?:string}
     */
    private function extract(\ZipArchive $zip, array $entries, string $root, string $staging): array
    {
        $prefix = $root === '' ? '' : $root . '/';
        foreach ($entries as $entry) {
            $relative = $prefix === '' ? $entry['name'] : substr($entry['name'], strlen($prefix));
            if ($relative === '' || str_contains($relative, "\0")) {
                return ['ok' => false, 'error' => 'ZIP 条目路径不合法。'];
            }
            $target = $staging . '/' . $relative;
            $parent = dirname($target);
            if (!is_dir($parent) && !mkdir($parent, 0755, true) && !is_dir($parent)) {
                return ['ok' => false, 'error' => '无法创建插件目录。'];
            }

            $stream = $zip->getStream((string)$entry['name']);
            if ($stream === false) {
                return ['ok' => false, 'error' => '无法读取 ZIP 条目：' . $entry['name']];
            }
            $out = @fopen($target, 'wb');
            if ($out === false) {
                fclose($stream);
                return ['ok' => false, 'error' => '无法写入插件文件。'];
            }
            $copied = stream_copy_to_stream($stream, $out);
            fclose($stream);
            fclose($out);
            if ($copied === false) {
                return ['ok' => false, 'error' => '写入插件文件失败。'];
            }
            @chmod($target, 0644);
        }
        return ['ok' => true];
    }

    private function createStagingDirectory(): string
    {
        $base = $this->appRoot . '/storage/tmp';
        if (!is_dir($base) && !mkdir($base, 0700, true) && !is_dir($base)) {
            return '';
        }
        if (!is_writable($base)) {
            return '';
        }
        $path = $base . '/plugin-' . bin2hex(random_bytes(10));
        if (!mkdir($path, 0700, true)) {
            return '';
        }
        return $path;
    }

    private function safeSlug(string $slug): string
    {
        $slug = trim($slug);
        if (!preg_match('/^[A-Za-z0-9][A-Za-z0-9_.-]{0,63}$/', $slug)) {
            return '';
        }
        return $slug;
    }

    private function removeDirectory(string $directory): void
    {
        if (!is_dir($directory)) {
            return;
        }
        $items = @scandir($directory);
        if ($items === false) {
            return;
        }
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            $path = $directory . '/' . $item;
            if (is_dir($path) && !is_link($path)) {
                $this->removeDirectory($path);
            } else {
                @unlink($path);
            }
        }
        @rmdir($directory);
    }

    /**
     * @param array<string,mixed> $entry
     */
    private function log(array $entry): void
    {
        $dir = $this->appRoot . '/storage/logs';
        if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
            return;
        }
        $line = json_encode([
            'time' => date('c'),
            'ip' => (string)($_SERVER['REMOTE_ADDR'] ?? ''),
            'user' => (string)($_SESSION['user'] ?? ''),
        ] + $entry, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($line !== false) {
            @file_put_contents($dir . '/plugin-install.log', $line . PHP_EOL, FILE_APPEND | LOCK_EX);
        }
    }

    /**
     * @return array{ok:bool,error:string}
     */
    private function fail(string $error): array
    {
        $this->log(['action' => 'install', 'result' => 'fail', 'error' => $error]);
        return ['ok' => false, 'error' => $error];
    }
}
