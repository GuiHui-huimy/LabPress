<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');
lp_guard_write_request();

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

// 体积上限（20MB）
if ((int)($_FILES['zip']['size'] ?? 0) > 20 * 1024 * 1024) {
    echo json_encode(['success' => false, 'error' => '插件包不能超过 20MB']);
    exit;
}

// 统一走安全解压流程（路径校验 / 单一根目录 / 体积上限 / 必须有 plugin.php）
$result = lp_install_plugin_zip($_FILES['zip']['tmp_name']);
if (empty($result['success'])) {
    echo json_encode(['success' => false, 'error' => $result['error'] ?? '安装失败']);
    exit;
}

echo json_encode([
    'success' => true,
    'slug' => $result['slug'],
    'plugin_info' => $result['plugin_info'],
]);
