<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/hooks.php';   // 确保钩子可用

header('Content-Type: application/json; charset=utf-8');
lp_guard_write_request();

if (!isset($_SESSION['user'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => '未登录']);
    exit;
}

if (!hasPermission('all')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => '无权限']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$slug = $input['slug'] ?? '';

if (empty($slug)) {
    echo json_encode(['success' => false, 'error' => '缺少插件标识']);
    exit;
}

// 安全过滤
$slug = preg_replace('/[^a-zA-Z0-9_-]/', '', $slug);

// 检查插件状态
$stmt = $pdo->prepare("SELECT status FROM plugins WHERE slug = ?");
$stmt->execute([$slug]);
$plugin = $stmt->fetch();
if (!$plugin) {
    echo json_encode(['success' => false, 'error' => '插件不存在']);
    exit;
}
if ($plugin['status'] == 1) {
    echo json_encode(['success' => false, 'error' => '插件已启用，请先停用再卸载']);
    exit;
}

// 先触发卸载钩子（在删除文件之前，这样插件还能执行清理逻辑）
LabPress_Hooks::doAction('plugin_uninstalled', $slug);

// 删除插件目录
$pluginDir = __DIR__ . '/../plugins/' . $slug;
if (is_dir($pluginDir)) {
    removeDir($pluginDir);
}

// 删除数据库记录
$stmt = $pdo->prepare("DELETE FROM plugins WHERE slug = ?");
$stmt->execute([$slug]);

echo json_encode(['success' => true, 'message' => '插件已卸载']);

function removeDir($dir) {
    if (!is_dir($dir)) return;
    $items = scandir($dir);
    foreach ($items as $item) {
        if ($item === '.' || $item === '..') continue;
        $path = $dir . DIRECTORY_SEPARATOR . $item;
        if (is_dir($path)) {
            removeDir($path);
        } else {
            unlink($path);
        }
    }
    rmdir($dir);
}