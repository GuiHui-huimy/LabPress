<?php
declare(strict_types=1);

/**
 * v1.2.0 最小烟雾测试（无 PHPUnit，便于共享主机/CI 直接跑）。
 * 用法：php tests/smoke.php
 */

require_once __DIR__ . '/../includes/config.php';

$failures = [];
$checks = 0;

function check(string $name, bool $condition, string $detail = ''): void
{
    global $failures, $checks;
    $checks++;
    if (!$condition) {
        $failures[] = $name . ($detail !== '' ? ' — ' . $detail : '');
    }
}

check('Config loaded', \LabPress\Config::get('db.name') !== '');
check('LABPRESS_VERSION', defined('LABPRESS_VERSION') && LABPRESS_VERSION !== '');

// Hook Engine：优先级、acceptedArgs、filter、remove、异常隔离
$order = [];
\LabPress\Hooks::reset();
\LabPress\Hooks::addAction('before_head_end', [\LabPress\Assets::class, 'renderStyles'], 20);
\LabPress\Hooks::addAction('footer_scripts', static function (): void {
    \LabPress\Assets::renderScripts(true);
}, 20);
\LabPress\Hooks::addAction('unit_hook', function (string $a): void {
    global $order;
    $order[] = 'first:' . $a;
}, 5, 1);
\LabPress\Hooks::addAction('unit_hook', function (string $a, string $b): void {
    global $order;
    $order[] = 'second:' . $a . ':' . $b;
}, 10, 2);
\LabPress\Hooks::doAction('unit_hook', 'A', 'B');
check('Hook priority + acceptedArgs', $order === ['first:A', 'second:A:B'], implode(',', $order));

\LabPress\Hooks::addFilter('unit_filter', static fn(string $v): string => $v . '-x');
\LabPress\Hooks::addFilter('unit_filter', static fn(string $v): string => $v . '-y', 20);
check('Filter chain', \LabPress\Hooks::applyFilters('unit_filter', 'v') === 'v-x-y');

\LabPress\Hooks::addAction('unit_throw', static function (): void {
    throw new RuntimeException('expected test exception');
});
$afterThrow = false;
\LabPress\Hooks::addAction('unit_throw', static function () use (&$afterThrow): void {
    $afterThrow = true;
});
\LabPress\Hooks::doAction('unit_throw');
check('Hook exception isolation', $afterThrow === true);

// Assets 注册表
\LabPress\Assets::reset();
\LabPress\Assets::addScript('assets/js/a.js', [], '1.2.0', true);
\LabPress\Assets::addScript('assets/js/b.js', ['assets/js/a.js'], '1.2.0', true);
\LabPress\Assets::addStyle('assets/css/a.css', [], '1.2.0');
ob_start();
\LabPress\Hooks::doAction('before_head_end');
\LabPress\Hooks::doAction('footer_scripts');
$assetsHtml = (string)ob_get_clean();
check('Assets style output', strpos($assetsHtml, 'assets/css/a.css?v=1.2.0') !== false);
check('Assets dependency order', strpos($assetsHtml, 'a.js') < strpos($assetsHtml, 'b.js'));
check('Assets one-time render', substr_count($assetsHtml, 'assets/css/a.css') === 1);

// Guard / Registry
$_SESSION['user'] = 'unit-test';
$_SESSION['permissions'] = ['all'];
check('Guard permission all', \LabPress\Guard::hasPermission('all'));
check('Guard csrf token', strlen(\LabPress\Guard::csrfToken()) === 64);

\LabPress\Admin\Registry::register('unit-page', 'Unit Page', static function (): void {
    echo 'UNIT_PAGE_OK';
}, 'all');
ob_start();
\LabPress\Admin\Registry::render('unit-page');
$pageHtml = (string)ob_get_clean();
check('Admin Registry render', $pageHtml === 'UNIT_PAGE_OK', $pageHtml);

$_SESSION['permissions'] = ['publications'];
\LabPress\Admin\Registry::register('unit-denied', 'Denied', static function (): void {
    echo 'SHOULD_NOT_RENDER';
}, 'users');
ob_start();
\LabPress\Admin\Registry::render('unit-denied');
$deniedHtml = (string)ob_get_clean();
check('Admin Registry permission deny', strpos($deniedHtml, 'SHOULD_NOT_RENDER') === false && strpos($deniedHtml, 'No permission') !== false, $deniedHtml);
$_SESSION['permissions'] = ['all'];

\LabPress\Admin\Registry::register('unit-error', 'Error', static function (): void {
    throw new RuntimeException('registry test exception');
}, 'all');
ob_start();
\LabPress\Admin\Registry::render('unit-error');
$errorHtml = (string)ob_get_clean();
check('Admin Registry exception catch', strpos($errorHtml, 'Page error') !== false, $errorHtml);

// Plugin Manager
$plugins = \LabPress\Plugin\Manager::discover();
check('Plugin discover', count($plugins) >= 2, (string)count($plugins));
check('Plugin active slugs', in_array('FooterInfo', \LabPress\Plugin\Manager::activeSlugs(), true));
$footerHeader = \LabPress\Plugin\Manager::parseHeader(__DIR__ . '/../plugins/FooterInfo/plugin.php');
check('Plugin header parse', ($footerHeader['name'] ?? '') === 'FooterInfo');
check('Plugin requires core', ($footerHeader['requires_core'] ?? '') !== '');

// 迁移
$runner = new \LabPress\Migration\Runner($pdo, __DIR__ . '/../migrations');
$status = $runner->status();
check('Migration count', count($status) === 3, (string)count($status));
foreach ($status as $row) {
    check('Migration applied: ' . $row['version'], $row['applied'] === true);
}

if ($failures !== []) {
    fwrite(STDERR, "SMOKE FAILED (" . count($failures) . "/" . $checks . ")\n");
    foreach ($failures as $failure) {
        fwrite(STDERR, ' - ' . $failure . "\n");
    }
    exit(1);
}

echo "SMOKE OK ({$checks} checks)\n";
