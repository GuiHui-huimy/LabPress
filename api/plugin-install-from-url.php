<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

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
$url = $input['download_url'] ?? '';

if (empty($url)) {
    echo json_encode(['success' => false, 'error' => '下载地址不能为空']);
    exit;
}

// 下载远程 ZIP
$tempName = tempnam(sys_get_temp_dir(), 'plugin_') . '.zip';
$zipContent = @file_get_contents($url);
if ($zipContent === false) {
    echo json_encode(['success' => false, 'error' => '无法下载插件']);
    exit;
}
file_put_contents($tempName, $zipContent);

// 解压安装
$zip = new ZipArchive();
if ($zip->open($tempName) !== true) {
    unlink($tempName);
    echo json_encode(['success' => false, 'error' => '无效的 ZIP 文件']);
    exit;
}

$pluginsDir = __DIR__ . '/../plugins/';
$rootFolder = $zip->getNameIndex(0);
$rootFolder = explode('/', $rootFolder)[0];
$targetDir = $pluginsDir . $rootFolder;

if (file_exists($targetDir)) {
    $zip->close();
    unlink($tempName);
    echo json_encode(['success' => false, 'error' => '插件已存在']);
    exit;
}

if (!$zip->extractTo($pluginsDir)) {
    $zip->close();
    unlink($tempName);
    echo json_encode(['success' => false, 'error' => '解压失败']);
    exit;
}
$zip->close();
unlink($tempName);

if (!file_exists($targetDir . '/plugin.php')) {
    // 删除无效目录
    removeDir($targetDir);
    echo json_encode(['success' => false, 'error' => '未找到 plugin.php']);
    exit;
}

echo json_encode(['success' => true, 'message' => '安装成功']);