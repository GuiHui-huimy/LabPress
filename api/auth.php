<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

$action = $_GET['action'] ?? '';

if ($action === 'login') {
    $input = json_decode(file_get_contents('php://input'), true);
    $username = $input['username'] ?? '';
    $password = $input['password'] ?? '';

    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password_hash'])) {
        $_SESSION['user'] = $user['username'];
        $_SESSION['permissions'] = json_decode($user['permissions'], true) ?? [];
        echo json_encode(['success' => true, 'permissions' => $_SESSION['permissions']]);
    } else {
        echo json_encode(['success' => false, 'error' => '用户名或密码错误']);
    }
} elseif ($action === 'logout') {
    session_destroy();
    echo json_encode(['success' => true]);
} elseif ($action === 'check') {
    echo json_encode([
        'logged_in'   => isset($_SESSION['user']),
        'user'        => $_SESSION['user'] ?? null,
        'permissions' => $_SESSION['permissions'] ?? []
    ]);
} else {
    http_response_code(400);
    echo json_encode(['error' => '无效操作']);
}