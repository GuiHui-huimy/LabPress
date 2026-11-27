<?php
declare(strict_types=1);

/**
 * LabPress 唯一引导入口（v1.2.0 起）。
 *
 * 负责：autoload、配置合并、session 启动、PDO 连接、时区、日志与资源钩子。
 * 不负责：站点内容配置（site_config 表由 LabPress::config() 读取）。
 */

if (defined('LABPRESS_BOOTSTRAPPED')) {
    return;
}
define('LABPRESS_BOOTSTRAPPED', true);
define('LABPRESS_ROOT', dirname(__DIR__));
define('LABPRESS_STORAGE', LABPRESS_ROOT . '/storage');
if (!defined('LABPRESS_VERSION')) {
    define('LABPRESS_VERSION', '1.2.0-dev');
}

spl_autoload_register(static function (string $class): void {
    $prefix = 'LabPress\\';
    if (strpos($class, $prefix) !== 0) {
        return;
    }
    $relative = substr($class, strlen($prefix));
    $file = LABPRESS_ROOT . '/core/' . str_replace('\\', '/', $relative) . '.php';
    if (is_file($file)) {
        require_once $file;
    }
});

$siteConfig = [];
$siteConfigFile = LABPRESS_STORAGE . '/config/site.php';
if (is_file($siteConfigFile)) {
    $loaded = require $siteConfigFile;
    if (is_array($loaded)) {
        $siteConfig = $loaded;
    }
}

// 兼容 v1.1.1：includes/config.php 里的 DB_* 常量仍然可作为回退。
$siteConfig['db'] = array_merge([
    'host' => defined('DB_HOST') ? DB_HOST : 'localhost',
    'name' => defined('DB_NAME') ? DB_NAME : '',
    'user' => defined('DB_USER') ? DB_USER : '',
    'pass' => defined('DB_PASS') ? DB_PASS : '',
    'charset' => 'utf8mb4',
], is_array($siteConfig['db'] ?? null) ? $siteConfig['db'] : []);

foreach ([
    'host' => 'LABPRESS_DB_HOST',
    'name' => 'LABPRESS_DB_NAME',
    'user' => 'LABPRESS_DB_USER',
    'pass' => 'LABPRESS_DB_PASS',
    'charset' => 'LABPRESS_DB_CHARSET',
] as $key => $envName) {
    $value = getenv($envName);
    if ($value !== false && $value !== '') {
        $siteConfig['db'][$key] = $value;
    }
}

$siteConfig += [
    'timezone' => 'Asia/Shanghai',
    'debug' => false,
    'session' => [],
];
if (getenv('LABPRESS_DEBUG') !== false) {
    $siteConfig['debug'] = filter_var(getenv('LABPRESS_DEBUG'), FILTER_VALIDATE_BOOLEAN);
}

$features = [];
$featuresFile = LABPRESS_STORAGE . '/config/features.php';
if (is_file($featuresFile)) {
    $loaded = require $featuresFile;
    if (is_array($loaded)) {
        $features = $loaded;
    }
}
$featuresLocal = LABPRESS_STORAGE . '/config/features.local.php';
if (is_file($featuresLocal)) {
    $loaded = require $featuresLocal;
    if (is_array($loaded)) {
        $features = array_merge($features, $loaded);
    }
}
$siteConfig['features'] = $features;

\LabPress\Config::load($siteConfig);
date_default_timezone_set((string)\LabPress\Config::get('timezone', 'Asia/Shanghai'));

if (!defined('LABPRESS_DEBUG')) {
    define('LABPRESS_DEBUG', (bool)\LabPress\Config::get('debug', false));
}

if (!function_exists('lp_bootstrap_session')) {
    function lp_bootstrap_session(): void
    {
        if (session_status() !== PHP_SESSION_NONE || headers_sent()) {
            return;
        }
        $session = \LabPress\Config::get('session', []);
        if (!is_array($session)) {
            $session = [];
        }
        $name = (string)($session['name'] ?? '');
        if ($name !== '' && preg_match('/^[A-Za-z0-9]+$/', $name)) {
            session_name($name);
        }
        $secure = $session['secure'] ?? null;
        if ($secure === null) {
            $secure = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
        }
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'secure' => (bool)$secure,
            'httponly' => true,
            'samesite' => (string)($session['same_site'] ?? 'Lax'),
        ]);
        session_start();
    }
}

lp_bootstrap_session();

if (!function_exists('lp_bootstrap_db')) {
    function lp_bootstrap_db(): PDO
    {
        global $pdo;
        if ($pdo instanceof PDO) {
            return $pdo;
        }
        $db = \LabPress\Config::get('db', []);
        if (!is_array($db) || empty($db['name'])) {
            throw new RuntimeException('Database is not configured. Create storage/config/site.php or fill includes/config.php.');
        }
        $dsn = 'mysql:host=' . ($db['host'] ?? 'localhost')
            . ';dbname=' . $db['name']
            . ';charset=' . ($db['charset'] ?? 'utf8mb4');
        $pdo = new PDO($dsn, (string)($db['user'] ?? ''), (string)($db['pass'] ?? ''), [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
        $pdo->exec("SET time_zone = '" . date('P') . "'");
        return $pdo;
    }
}

try {
    $pdo = lp_bootstrap_db();
} catch (Throwable $e) {
    \LabPress\Support\Logger::exception($e, ['stage' => 'db_bootstrap']);
    if (PHP_SAPI === 'cli') {
        fwrite(STDERR, 'Database bootstrap failed: ' . $e->getMessage() . PHP_EOL);
        exit(1);
    }
    http_response_code(500);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Database Connect Error';
    exit;
}

// 统一异常处理：不向浏览器回显堆栈。
set_exception_handler(static function (Throwable $e): void {
    \LabPress\Support\Logger::exception($e, ['stage' => 'uncaught']);
    if (PHP_SAPI === 'cli') {
        fwrite(STDERR, 'Uncaught: ' . $e->getMessage() . PHP_EOL);
        exit(1);
    }
    if (!headers_sent()) {
        http_response_code(500);
        $isJson = strpos((string)($_SERVER['REQUEST_URI'] ?? ''), '/api/') !== false
            || strpos((string)($_SERVER['HTTP_ACCEPT'] ?? ''), 'application/json') !== false;
        header('Content-Type: ' . ($isJson ? 'application/json' : 'text/html') . '; charset=utf-8');
        header('X-Request-Id: ' . \LabPress\Support\Logger::requestId());
    }
    $requestId = \LabPress\Support\Logger::requestId();
    $isJson = strpos((string)($_SERVER['REQUEST_URI'] ?? ''), '/api/') !== false
        || strpos((string)($_SERVER['HTTP_ACCEPT'] ?? ''), 'application/json') !== false;
    if ($isJson) {
        echo json_encode(['ok' => false, 'error' => ['code' => 'E_SERVER', 'message' => 'Internal error'], 'request_id' => $requestId]);
    } else {
        echo '<!doctype html><meta charset="utf-8"><title>Service unavailable</title><h1>Service unavailable</h1><p>Request: ' . htmlspecialchars($requestId, ENT_QUOTES) . '</p>';
    }
    exit;
});

set_error_handler(static function (int $severity, string $message, string $file, int $line): bool {
    if (!(error_reporting() & $severity)) {
        return false;
    }
    \LabPress\Support\Logger::warning($message, ['file' => $file, 'line' => $line, 'severity' => $severity]);
    return false;
});

// 资源注册表：所有页面在 before_head_end / footer_scripts 自动输出。
\LabPress\Hooks::addAction('before_head_end', [\LabPress\Assets::class, 'renderStyles'], 20);
\LabPress\Hooks::addAction('admin_before_head_end', [\LabPress\Assets::class, 'renderStyles'], 20);
\LabPress\Hooks::addAction('footer_scripts', static function (): void {
    \LabPress\Assets::renderScripts(true);
}, 20);

if (PHP_SAPI === 'cli' && !defined('LABPRESS_CLI_BOOTSTRAP')) {
    define('LABPRESS_CLI_BOOTSTRAP', true);
}
