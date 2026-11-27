<?php
declare(strict_types=1);

/**
 * LabPress 初始安装入口。
 *
 * 直接访问 /install.php 即可；安装完成后会生成 storage/install.lock，
 * 再次访问只显示“已安装”，不会重复建表。
 */

define('LABPRESS_INSTALLER', true);
define('LABPRESS_ROOT', __DIR__);

require_once __DIR__ . '/install/Schema.php';
require_once __DIR__ . '/install/DemoData.php';
require_once __DIR__ . '/install/Installer.php';

use LabPress\Install\Installer;

function h(?string $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

$installer = new Installer(__DIR__);
$installed = $installer->isInstalled();

$secure = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
if (session_status() === PHP_SESSION_NONE) {
    session_name('LABPRESS_INSTALL');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'httponly' => true,
        'samesite' => 'Lax',
        'secure' => $secure,
    ]);
    @session_start();
}

function install_csrf(): string
{
    if (empty($_SESSION['install_csrf'])) {
        $_SESSION['install_csrf'] = bin2hex(random_bytes(16));
    }
    return (string)$_SESSION['install_csrf'];
}

function install_defaults(): array
{
    $host = (string)($_SERVER['HTTP_HOST'] ?? 'localhost');
    $guessUrl = ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://') . $host;
    return [
        'db_host' => '127.0.0.1',
        'db_port' => '3306',
        'db_socket' => '',
        'db_name' => 'labpress',
        'db_user' => '',
        'db_pass' => '',
        'site_name' => 'LabPress Demo Lab',
        'site_url' => $guessUrl,
        'admin_username' => 'admin',
        'admin_password' => '',
        'language' => 'zh_CN',
        'demo' => true,
    ];
}

function install_render(string $view, array $vars = [], int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: text/html; charset=utf-8');
    header('Cache-Control: no-store, no-cache, must-revalidate');
    header('X-Content-Type-Options: nosniff');
    extract($vars, EXTR_SKIP);
    require __DIR__ . '/install/views/head.php';
    require __DIR__ . '/install/views/' . $view . '.php';
    require __DIR__ . '/install/views/foot.php';
    exit;
}

if ($installed) {
    install_render('installed', [
        'pageTitle' => 'LabPress 已安装',
        'lock' => $installer->lockInfo(),
    ]);
}

$errors = [];
$old = install_defaults();
$checks = $installer->preflight();
$passed = $installer->preflightPassed();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $attempts = $_SESSION['install_attempts'] ?? [];
    $now = time();
    $attempts = array_values(array_filter((array)$attempts, static fn($t): bool => is_int($t) && $t > $now - 3600));
    if (count($attempts) >= 10) {
        $errors[] = '尝试次数过多，请在一小时后再试。';
    } else {
        $attempts[] = $now;
        $_SESSION['install_attempts'] = $attempts;

        $token = (string)($_POST['_csrf'] ?? '');
        $expected = (string)($_SESSION['install_csrf'] ?? '');
        if ($expected === '' || !hash_equals($expected, $token)) {
            $errors[] = '页面已过期，请刷新后重试。';
        } else {
            $old = [
                'db_host' => (string)($_POST['db_host'] ?? ''),
                'db_port' => (string)($_POST['db_port'] ?? '3306'),
                'db_socket' => (string)($_POST['db_socket'] ?? ''),
                'db_name' => (string)($_POST['db_name'] ?? ''),
                'db_user' => (string)($_POST['db_user'] ?? ''),
                'db_pass' => '',
                'site_name' => (string)($_POST['site_name'] ?? ''),
                'site_url' => (string)($_POST['site_url'] ?? ''),
                'admin_username' => (string)($_POST['admin_username'] ?? ''),
                'admin_password' => '',
                'language' => (string)($_POST['language'] ?? 'zh_CN'),
                'demo' => isset($_POST['demo']),
            ];
        }

        if ($errors === []) {
            $result = $installer->run([
                'db_host' => $old['db_host'],
                'db_port' => $old['db_port'],
                'db_socket' => $old['db_socket'],
                'db_name' => $old['db_name'],
                'db_user' => $old['db_user'],
                'db_pass' => (string)($_POST['db_pass'] ?? ''),
                'site_name' => $old['site_name'],
                'site_url' => $old['site_url'],
                'admin_username' => $old['admin_username'],
                'admin_password' => (string)($_POST['admin_password'] ?? ''),
                'language' => $old['language'],
                'demo' => $old['demo'],
            ]);

            if ($result['ok']) {
                unset($_SESSION['install_csrf'], $_SESSION['install_attempts']);
                install_render('success', [
                    'pageTitle' => '安装完成 · LabPress',
                    'admin' => (string)$result['admin_username'],
                    'demo' => (bool)$result['demo'],
                    'db' => (string)$result['db_name'],
                    'url' => rtrim($old['site_url'], '/'),
                    'schemaVersion' => (string)($result['schema_version'] ?? ''),
                ]);
            }
            $errors = $result['errors'];
            $checks = $installer->preflight();
            $passed = $installer->preflightPassed();
        }
    }
}

install_render('form', [
    'pageTitle' => '安装 LabPress · 1.2.0',
    'checks' => $checks,
    'passed' => $passed,
    'csrf' => install_csrf(),
    'old' => $old,
    'errors' => $errors,
]);
