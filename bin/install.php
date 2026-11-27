<?php
declare(strict_types=1);

/**
 * LabPress 安装器 CLI 入口。
 *
 * 用法：
 * php bin/install.php \
 *   --db-host=127.0.0.1 --db-name=labpress --db-user=labpress --db-pass=secret \
 *   --site-url=https://example.com --site-name="LabPress" \
 *   --admin-username=admin --admin-password="change-me-now" --demo
 *
 * 参数也可以通过 LABPRESS_DB_HOST / LABPRESS_DB_NAME / LABPRESS_DB_USER /
 * LABPRESS_DB_PASS / LABPRESS_SITE_URL / LABPRESS_ADMIN_USER /
 * LABPRESS_ADMIN_PASS 环境变量提供。
 */

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "This script must be run from CLI.\n");
    exit(1);
}

define('LABPRESS_ROOT', dirname(__DIR__));
define('LABPRESS_INSTALLER', true);
require_once LABPRESS_ROOT . '/install/Schema.php';
require_once LABPRESS_ROOT . '/install/DemoData.php';
require_once LABPRESS_ROOT . '/install/Installer.php';

use LabPress\Install\Installer;

$options = [];
foreach (array_slice($argv, 1) as $arg) {
    if ($arg === '--demo') {
        $options['demo'] = true;
        continue;
    }
    if ($arg === '--no-demo') {
        $options['demo'] = false;
        continue;
    }
    if (str_starts_with($arg, '--')) {
        $pair = explode('=', substr($arg, 2), 2);
        if (count($pair) === 2) {
            $options[$pair[0]] = $pair[1];
        }
    }
}

$value = static function (string $key, string $env, string $default = '') use ($options): string {
    if (array_key_exists($key, $options)) {
        return (string)$options[$key];
    }
    $envValue = getenv($env);
    return $envValue !== false ? (string)$envValue : $default;
};

$input = [
    'db_host' => $value('db-host', 'LABPRESS_DB_HOST', '127.0.0.1'),
    'db_port' => $value('db-port', 'LABPRESS_DB_PORT', '3306'),
    'db_socket' => $value('db-socket', 'LABPRESS_DB_SOCKET', ''),
    'db_name' => $value('db-name', 'LABPRESS_DB_NAME', 'labpress'),
    'db_user' => $value('db-user', 'LABPRESS_DB_USER', ''),
    'db_pass' => $value('db-pass', 'LABPRESS_DB_PASS', ''),
    'site_url' => $value('site-url', 'LABPRESS_SITE_URL', 'http://127.0.0.1'),
    'site_name' => $value('site-name', 'LABPRESS_SITE_NAME', 'LabPress'),
    'admin_username' => $value('admin-username', 'LABPRESS_ADMIN_USER', 'admin'),
    'admin_password' => $value('admin-password', 'LABPRESS_ADMIN_PASS', ''),
    'language' => $value('language', 'LABPRESS_LANGUAGE', 'zh_CN'),
    'demo' => $options['demo'] ?? false,
];

$installer = new Installer(LABPRESS_ROOT);
$result = $installer->run($input);

if (!$result['ok']) {
    fwrite(STDERR, "Install failed at [{$result['stage']}]:\n");
    foreach ($result['errors'] as $error) {
        fwrite(STDERR, ' - ' . $error . PHP_EOL);
    }
    exit(1);
}

echo "Install completed.\n";
echo "  database: {$result['db_name']}\n";
echo "  admin:    {$result['admin_username']}\n";
echo "  schema:   {$result['schema_version']}\n";
echo '  demo:     ' . ($result['demo'] ? 'yes' : 'no') . "\n";
exit(0);
