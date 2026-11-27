<?php
declare(strict_types=1);

namespace LabPress\Install;

use PDO;
use PDOException;
use RuntimeException;
use Throwable;

if (!defined('LABPRESS_INSTALLER')) {
    http_response_code(404);
    exit;
}

/**
 * LabPress 浏览器 / CLI 安装器。
 *
 * 安装顺序：
 * 1. 校验 PHP 环境与 storage 写权限
 * 2. 连接 MySQL server，按需创建空数据库
 * 3. 建立 1.2.0 基础表，并执行现有迁移
 * 4. 写入管理员、站点配置和可选 demo 数据
 * 5. 写 storage/config/site.php 与 storage/install.lock
 *
 * 设计原则：不导入 SQL 文件、不覆盖已有表、安装失败可重试。
 */
final class Installer
{
    public const VERSION = '1.2.0';
    public const CONFIG_FILE = 'storage/config/site.php';
    public const LOCK_FILE = 'storage/install.lock';

    public function __construct(private string $root)
    {
        $this->root = rtrim($root, DIRECTORY_SEPARATOR);
    }

    public function isInstalled(): bool
    {
        return is_file($this->path(self::LOCK_FILE));
    }

    /** @return array<string,mixed> */
    public function lockInfo(): array
    {
        $file = $this->path(self::LOCK_FILE);
        if (!is_file($file)) {
            return [];
        }
        $raw = @file_get_contents($file);
        $data = is_string($raw) ? json_decode($raw, true) : null;
        return is_array($data) ? $data : ['raw' => $raw];
    }

    /**
     * @return array<int,array{key:string,label:string,ok:bool,detail:string,required:bool}>
     */
    public function preflight(): array
    {
        $storage = $this->path('storage');
        $configDir = $this->path('storage/config');

        return [
            [
                'key' => 'php',
                'label' => 'PHP >= 8.1',
                'ok' => version_compare(PHP_VERSION, '8.1.0', '>='),
                'detail' => PHP_VERSION,
                'required' => true,
            ],
            [
                'key' => 'pdo_mysql',
                'label' => 'PDO MySQL 扩展',
                'ok' => extension_loaded('pdo') && extension_loaded('pdo_mysql'),
                'detail' => extension_loaded('pdo_mysql') ? 'pdo_mysql 已启用' : '缺少 pdo_mysql',
                'required' => true,
            ],
            [
                'key' => 'json',
                'label' => 'JSON 扩展',
                'ok' => extension_loaded('json'),
                'detail' => extension_loaded('json') ? 'json 已启用' : '缺少 json',
                'required' => true,
            ],
            [
                'key' => 'mbstring',
                'label' => 'Multibyte String',
                'ok' => extension_loaded('mbstring'),
                'detail' => extension_loaded('mbstring') ? 'mbstring 已启用' : '缺少 mbstring',
                'required' => true,
            ],
            [
                'key' => 'session',
                'label' => 'Session 扩展',
                'ok' => extension_loaded('session'),
                'detail' => extension_loaded('session') ? 'session 已启用' : '缺少 session',
                'required' => true,
            ],
            [
                'key' => 'storage',
                'label' => 'storage 目录可写',
                'ok' => is_dir($storage) && is_writable($storage),
                'detail' => is_dir($storage) ? (is_writable($storage) ? '可写' : '不可写') : '目录不存在',
                'required' => true,
            ],
            [
                'key' => 'config',
                'label' => 'storage/config 可写',
                'ok' => is_dir($configDir) && is_writable($configDir),
                'detail' => is_dir($configDir) ? (is_writable($configDir) ? '可写' : '不可写') : '目录不存在',
                'required' => true,
            ],
            [
                'key' => 'zip',
                'label' => 'ZIP 扩展（插件安装）',
                'ok' => class_exists('ZipArchive'),
                'detail' => class_exists('ZipArchive') ? '可用于在线安装插件' : '缺少 ZipArchive，仅影响插件 ZIP 安装',
                'required' => false,
            ],
            [
                'key' => 'not_installed',
                'label' => '尚未安装',
                'ok' => !$this->isInstalled(),
                'detail' => $this->isInstalled() ? '检测到 storage/install.lock' : '可继续安装',
                'required' => true,
            ],
        ];
    }

    public function preflightPassed(): bool
    {
        foreach ($this->preflight() as $check) {
            if (($check['required'] ?? false) && empty($check['ok'])) {
                return false;
            }
        }
        return true;
    }

    /**
     * @param array<string,mixed> $input
     * @return array{ok:bool,stage:string,errors:array<int,string>,admin_username?:string,demo?:bool,db_name?:string,schema_version?:string}
     */
    public function run(array $input): array
    {
        $stage = 'validate';
        $pdo = null;

        try {
            if (!$this->preflightPassed()) {
                throw new RuntimeException('服务器环境未通过必要检查，请先处理标红的项目。');
            }

            $data = $this->normalize($input);
            $stage = 'storage';
            $this->ensureStorage();

            $stage = 'connect';
            $pdo = $this->connectAndCreateDatabase($data);

            $stage = 'schema';
            foreach (Schema::statements() as $statement) {
                $pdo->exec($statement);
            }

            $stage = 'migrations';
            $this->runMigrations($pdo);

            $stage = 'seed';
            $pdo->beginTransaction();
            try {
                $this->seedBase($pdo, $data);
                if (!empty($data['demo'])) {
                    DemoData::seed($pdo, $data['site_name'], $data['site_url']);
                }
                $pdo->commit();
            } catch (Throwable $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                throw $e;
            }

            $stage = 'config';
            $this->writeConfig($data);

            $stage = 'lock';
            $schemaVersion = $this->currentMigration($pdo);
            $this->writeLock($data, $schemaVersion);

            return [
                'ok' => true,
                'stage' => 'done',
                'errors' => [],
                'admin_username' => $data['admin_username'],
                'demo' => (bool)$data['demo'],
                'db_name' => $data['db_name'],
                'schema_version' => $schemaVersion,
            ];
        } catch (Throwable $e) {
            return [
                'ok' => false,
                'stage' => $stage,
                'errors' => [$this->friendlyError($e, $stage)],
            ];
        }
    }

    /**
     * @param array<string,mixed> $input
     * @return array<string,mixed>
     */
    private function normalize(array $input): array
    {
        $host = trim((string)($input['db_host'] ?? ''));
        $socket = trim((string)($input['db_socket'] ?? ''));
        $port = (int)($input['db_port'] ?? 3306);
        $name = trim((string)($input['db_name'] ?? ''));
        $user = trim((string)($input['db_user'] ?? ''));
        $pass = (string)($input['db_pass'] ?? '');
        $siteUrl = rtrim(trim((string)($input['site_url'] ?? '')), '/');
        $siteName = trim((string)($input['site_name'] ?? ''));
        $admin = trim((string)($input['admin_username'] ?? ''));
        $adminPass = (string)($input['admin_password'] ?? '');
        $language = (string)($input['language'] ?? 'zh_CN');

        if ($host === '') {
            throw new RuntimeException('请填写数据库主机地址。');
        }
        if (preg_match('/[\s;]/u', $host) || strlen($host) > 255) {
            throw new RuntimeException('数据库主机地址格式不正确。');
        }
        if ($socket !== '') {
            if ($socket[0] !== '/' || preg_match('/[\s;]/u', $socket)) {
                throw new RuntimeException('Unix Socket 路径格式不正确。');
            }
        }
        if ($port < 1 || $port > 65535) {
            throw new RuntimeException('数据库端口应在 1-65535 之间。');
        }
        if (!preg_match('/^[A-Za-z0-9_]{1,64}$/', $name)) {
            throw new RuntimeException('数据库名只能包含字母、数字和下划线，且不超过 64 个字符。');
        }
        if ($user === '' || preg_match('/\s/u', $user)) {
            throw new RuntimeException('请填写有效的数据库用户名。');
        }
        if (!preg_match('#^https?://[^\s]+$#i', $siteUrl) || !filter_var($siteUrl, FILTER_VALIDATE_URL)) {
            throw new RuntimeException('站点 URL 必须是完整的 http:// 或 https:// 地址。');
        }
        if ($siteName === '' || mb_strlen($siteName) > 100) {
            throw new RuntimeException('站点名称不能为空，且不超过 100 个字符。');
        }
        if (!preg_match('/^[A-Za-z0-9_.@-]{3,32}$/', $admin)) {
            throw new RuntimeException('管理员用户名需为 3-32 位字母、数字或 _ . @ -。');
        }
        if (strlen($adminPass) < 8) {
            throw new RuntimeException('管理员密码至少需要 8 个字符。');
        }
        if (!in_array($language, ['zh_CN', 'en_US'], true)) {
            $language = 'zh_CN';
        }

        return [
            'db_host' => $host,
            'db_socket' => $socket,
            'db_port' => $port,
            'db_name' => $name,
            'db_user' => $user,
            'db_pass' => $pass,
            'site_url' => $siteUrl,
            'site_name' => $siteName,
            'admin_username' => $admin,
            'admin_password' => $adminPass,
            'language' => $language,
            'demo' => !empty($input['demo']),
        ];
    }

    private function ensureStorage(): void
    {
        foreach (['storage', 'storage/config', 'storage/logs'] as $dir) {
            $path = $this->path($dir);
            if (!is_dir($path) && !@mkdir($path, 0775, true) && !is_dir($path)) {
                throw new RuntimeException('无法创建目录：' . $dir);
            }
        }
        if (!is_writable($this->path('storage/config'))) {
            throw new RuntimeException('storage/config 不可写，请调整目录权限。');
        }
    }

    /** @param array<string,mixed> $data */
    private function connectAndCreateDatabase(array $data): PDO
    {
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_TIMEOUT => 8,
        ];

        $serverDsn = $this->dsn($data, false);
        $server = new PDO($serverDsn, $data['db_user'], $data['db_pass'], $options);
        $created = false;

        try {
            $server->exec(
                'CREATE DATABASE IF NOT EXISTS `' . $data['db_name'] . '` '
                . 'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci'
            );
            $created = true;
        } catch (PDOException $e) {
            // 共享主机常由面板预先建库；此时用户没有 CREATE DATABASE 权限。
            // 下面直接尝试连接目标库，仍失败再把原始错误抛给调用方。
            try {
                $target = new PDO($this->dsn($data, true), $data['db_user'], $data['db_pass'], $options);
                return $target;
            } catch (PDOException $targetError) {
                throw new RuntimeException('无法创建或连接数据库：' . $this->shortMessage($e), 0, $e);
            }
        }

        if ($created) {
            $server->exec('USE `' . $data['db_name'] . '`');
        }

        return new PDO($this->dsn($data, true), $data['db_user'], $data['db_pass'], $options);
    }

    /** @param array<string,mixed> $data */
    private function dsn(array $data, bool $withDatabase): string
    {
        $socket = (string)($data['db_socket'] ?? '');
        if ($socket !== '') {
            $dsn = 'mysql:unix_socket=' . $socket;
        } else {
            $dsn = 'mysql:host=' . $data['db_host'] . ';port=' . (int)$data['db_port'];
        }
        if ($withDatabase) {
            $dsn .= ';dbname=' . $data['db_name'];
        }
        return $dsn . ';charset=utf8mb4';
    }

    private function runMigrations(PDO $pdo): void
    {
        $runnerFile = $this->path('core/Migration/Runner.php');
        if (!is_file($runnerFile)) {
            throw new RuntimeException('迁移执行器不存在，请检查 core/Migration/Runner.php。');
        }
        require_once $runnerFile;
        $runner = new \LabPress\Migration\Runner($pdo, $this->path('migrations'));
        $runner->up();
    }

    private function currentMigration(PDO $pdo): string
    {
        $stmt = $pdo->query('SELECT version FROM `schema_version` ORDER BY version DESC LIMIT 1');
        $version = $stmt->fetchColumn();
        return $version === false ? Schema::VERSION : (string)$version;
    }

    /** @param array<string,mixed> $data */
    private function seedBase(PDO $pdo, array $data): void
    {
        $configs = [
            'site_name' => $data['site_name'],
            'site_title' => $data['site_name'] . ' · LabPress',
            'title_format' => '{page} - {site}',
            'title_separator' => ' - ',
            'language' => $data['language'],
            'ml_source_locale' => $data['language'],
        ];
        $this->upsertConfigs($pdo, $configs);

        $permissions = json_encode(['all'], JSON_UNESCAPED_UNICODE);
        $stmt = $pdo->prepare(
            'INSERT INTO `users` (`username`, `password_hash`, `permissions`, `created_at`)
             VALUES (?, ?, ?, NOW())
             ON DUPLICATE KEY UPDATE `password_hash` = VALUES(`password_hash`), `permissions` = VALUES(`permissions`)'
        );
        $stmt->execute([
            $data['admin_username'],
            password_hash($data['admin_password'], PASSWORD_DEFAULT),
            $permissions,
        ]);
    }

    /** @param array<string,string> $configs */
    private function upsertConfigs(PDO $pdo, array $configs): void
    {
        $stmt = $pdo->prepare(
            'INSERT INTO `site_config` (`config_key`, `config_value`, `updated_at`)
             VALUES (?, ?, NOW())
             ON DUPLICATE KEY UPDATE `config_value` = VALUES(`config_value`), `updated_at` = NOW()'
        );
        foreach ($configs as $key => $value) {
            $stmt->execute([$key, (string)$value]);
        }
    }

    /** @param array<string,mixed> $data */
    private function writeConfig(array $data): void
    {
        $file = $this->path(self::CONFIG_FILE);
        $dir = dirname($file);
        if (!is_dir($dir) && !@mkdir($dir, 0775, true) && !is_dir($dir)) {
            throw new RuntimeException('无法创建 storage/config 目录。');
        }

        if (is_file($file)) {
            $backup = $file . '.bak.' . date('YmdHis');
            @copy($file, $backup);
        }

        $config = [
            'db' => [
                'host' => $data['db_host'],
                'port' => (int)$data['db_port'],
                'socket' => $data['db_socket'],
                'name' => $data['db_name'],
                'user' => $data['db_user'],
                'pass' => $data['db_pass'],
                'charset' => 'utf8mb4',
            ],
            'timezone' => 'Asia/Shanghai',
            'debug' => false,
            'session' => [
                'name' => '',
                'same_site' => 'Lax',
                'secure' => null,
            ],
        ];

        $php = "<?php\n"
            . "/**\n"
            . " * LabPress 部署配置。\n"
            . " * 由安装器生成；包含数据库凭据，请勿提交到版本库。\n"
            . " */\n"
            . 'return ' . var_export($config, true) . ";\n";

        $tmp = $file . '.tmp.' . bin2hex(random_bytes(4));
        if (@file_put_contents($tmp, $php, LOCK_EX) === false) {
            throw new RuntimeException('无法写入 storage/config/site.php。');
        }
        @chmod($tmp, 0640);
        if (!@rename($tmp, $file)) {
            @unlink($tmp);
            throw new RuntimeException('无法替换 storage/config/site.php，请检查目录权限。');
        }
        @chmod($file, 0640);
    }

    /** @param array<string,mixed> $data */
    private function writeLock(array $data, string $schemaVersion): void
    {
        $payload = [
            'installed_at' => date('c'),
            'version' => self::VERSION,
            'schema_version' => $schemaVersion,
            'admin_username' => $data['admin_username'],
            'demo' => (bool)$data['demo'],
            'db_name' => $data['db_name'],
            'site_url' => $data['site_url'],
        ];
        $file = $this->path(self::LOCK_FILE);
        $json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
        if ($json === false || @file_put_contents($file, $json, LOCK_EX) === false) {
            throw new RuntimeException('无法写入 storage/install.lock。');
        }
        @chmod($file, 0644);
    }

    private function friendlyError(Throwable $e, string $stage): string
    {
        $message = trim($e->getMessage());
        if ($stage === 'connect') {
            if (stripos($message, 'Access denied') !== false) {
                return '数据库用户名或密码不正确，或该用户没有访问权限。';
            }
            if (stripos($message, 'Unknown database') !== false) {
                return '数据库不存在，且当前用户没有创建数据库的权限。请先在面板中创建空数据库。';
            }
            if (stripos($message, 'could not find driver') !== false) {
                return '服务器缺少 pdo_mysql 扩展。';
            }
            return '数据库连接失败：' . $this->shortMessage($e);
        }
        if ($stage === 'schema' || $stage === 'migrations') {
            return '数据库表初始化失败：' . $this->shortMessage($e);
        }
        if ($stage === 'config' || $stage === 'lock') {
            return $this->shortMessage($e);
        }
        return $this->shortMessage($e);
    }

    private function shortMessage(Throwable $e): string
    {
        $message = preg_replace('/\s+/u', ' ', trim($e->getMessage())) ?: '未知错误';
        if (mb_strlen($message) > 220) {
            $message = mb_substr($message, 0, 220) . '...';
        }
        return $message;
    }

    private function path(string $relative): string
    {
        return $this->root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relative);
    }
}
