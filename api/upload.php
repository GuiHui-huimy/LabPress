<?php
session_start();
require_once '../includes/config.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');

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
$allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
if (!in_array($file['type'], $allowed)) {
    echo json_encode(['success' => false, 'error' => '仅支持 JPG/PNG/GIF/WebP']);
    exit;
}

// 根据上传类型决定子目录
$type = isset($_POST['type']) ? preg_replace('/[^a-zA-Z0-9_-]/', '', $_POST['type']) : 'general';
$subDir = 'uploads/' . $type . '/';
$targetDir = __DIR__ . '/../' . $subDir;

if (!is_dir($targetDir)) {
    mkdir($targetDir, 0755, true);
}

$ext = pathinfo($file['name'], PATHINFO_EXTENSION);
$newName = $type . '-' . time() . '.' . $ext;
$targetPath = $targetDir . $newName;

if (move_uploaded_file($file['tmp_name'], $targetPath)) {
    $url = $subDir . $newName;
    echo json_encode(['success' => true, 'url' => $url]);
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => '保存失败']);
}