<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

// 登录验证
if (!isset($_SESSION['user'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => '未登录']);
    exit;
}

// 权限检查
if (!hasPermission('all')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => '无权限']);
    exit;
}

if (!isset($_FILES['zip']) || $_FILES['zip']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'error' => '文件上传失败']);
    exit;
}

$file = $_FILES['zip'];
$ext = pathinfo($file['name'], PATHINFO_EXTENSION);
if (strtolower($ext) !== 'zip') {
    echo json_encode(['success' => false, 'error' => '仅支持 ZIP 文件']);
    exit;
}

$zip = new ZipArchive();
if ($zip->open($file['tmp_name']) !== true) {
    echo json_encode(['success' => false, 'error' => '无法打开 ZIP 文件']);
    exit;
}

$pluginsDir = __DIR__ . '/../plugins/';
// 获取 ZIP 根目录的文件夹名
$firstEntry = $zip->getNameIndex(0);
$rootFolder = explode('/', $firstEntry)[0] ?? '';
if (empty($rootFolder)) {
    $zip->close();
    echo json_encode(['success' => false, 'error' => '无效的插件压缩包结构']);
    exit;
}

$targetDir = $pluginsDir . $rootFolder;
if (file_exists($targetDir)) {
    $zip->close();
    echo json_encode(['success' => false, 'error' => '插件目录已存在，请先删除再安装']);
    exit;
}

// 解压
if (!$zip->extractTo($pluginsDir)) {
    $zip->close();
    echo json_encode(['success' => false, 'error' => '解压失败']);
    exit;
}
$zip->close();

// 检查 plugin.php
$pluginFile = $targetDir . '/plugin.php';
if (!file_exists($pluginFile)) {
    removeDir($targetDir);
    echo json_encode(['success' => false, 'error' => '压缩包中未找到 plugin.php 文件']);
    exit;
}

// 解析插件信息
$pluginInfo = [
    'name' => $rootFolder,
    'description' => '',
    'version' => '',
    'author' => ''
];
$content = file_get_contents($pluginFile);
if (preg_match('/Plugin Name:\s*(.+)/i', $content, $m)) $pluginInfo['name'] = trim($m[1]);
if (preg_match('/Description:\s*(.+)/i', $content, $m)) $pluginInfo['description'] = trim($m[1]);
if (preg_match('/Version:\s*(.+)/i', $content, $m)) $pluginInfo['version'] = trim($m[1]);
if (preg_match('/Author:\s*(.+)/i', $content, $m)) $pluginInfo['author'] = trim($m[1]);

echo json_encode(['success' => true, 'plugin_info' => $pluginInfo]);

/**
 * 递归删除目录
 */
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