<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');
lp_guard_write_request();

if (!isset($_SESSION['user'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => '未登录']);
    exit;
}

if (empty($_SESSION['permissions'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => '无权限']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_FILES['image'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => '请选择图片']);
    exit;
}

$file = $_FILES['image'];
if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => '上传失败，请重试']);
    exit;
}

// 体积上限（5MB）
if ((int)($file['size'] ?? 0) > 5 * 1024 * 1024) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => '图片不能超过 5MB']);
    exit;
}

// 以服务端探测到的真实 MIME 为准，扩展名由 MIME 反推
$allowed = [
    'image/jpeg' => 'jpg',
    'image/png'  => 'png',
    'image/gif'  => 'gif',
    'image/webp' => 'webp',
];
$mime = '';
if (class_exists('finfo')) {
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = (string)$finfo->file($file['tmp_name']);
} elseif (function_exists('mime_content_type')) {
    $mime = (string)mime_content_type($file['tmp_name']);
} else {
    // 极端兜底：服务端探测能力缺失时退回客户端声明（仍受扩展名白名单约束）
    $mime = (string)($file['type'] ?? '');
}
if (!isset($allowed[$mime])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => '仅支持 JPG/PNG/GIF/WebP']);
    exit;
}

// 根据上传类型决定子目录（只允许安全字符）
$type = isset($_POST['type']) ? (string)$_POST['type'] : 'general';
$type = preg_replace('/[^a-zA-Z0-9_-]/', '', $type);
if ($type === '' || strlen($type) > 32) {
    $type = 'general';
}
$subDir = 'uploads/' . $type . '/';
$targetDir = __DIR__ . '/../' . $subDir;

if (!is_dir($targetDir) && !mkdir($targetDir, 0755, true) && !is_dir($targetDir)) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => '上传目录不可写']);
    exit;
}

// 随机文件名，避免覆盖与可猜测路径
$newName = $type . '-' . date('Ymd') . '-' . bin2hex(random_bytes(8)) . '.' . $allowed[$mime];
$targetPath = $targetDir . $newName;

if (move_uploaded_file($file['tmp_name'], $targetPath)) {
    $url = $subDir . $newName;
    echo json_encode(['success' => true, 'url' => $url]);
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => '保存失败']);
}
