<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

$action = $_GET['action'] ?? '';

if ($action === 'login') {
    $input = json_decode(file_get_contents('php://input'), true);
    $username = (string)($input['username'] ?? '');
    $password = (string)($input['password'] ?? '');

    if (!\LabPress\Guard::loginAllowed($username)) {
        \LabPress\Guard::audit('login_rate_limited', $username, 'denied', ['ip' => \LabPress\Guard::clientIp()]);
        http_response_code(429);
        echo json_encode(['success' => false, 'error' => '尝试次数过多，请稍后再试', 'code' => 'E_RATE_LIMIT']);
        exit;
    }

    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password_hash'])) {
        session_regenerate_id(true);
        $_SESSION['user'] = $user['username'];
        $_SESSION['permissions'] = json_decode($user['permissions'], true) ?? [];
        \LabPress\Guard::clearLoginFailures($username);
        \LabPress\Guard::audit('login_success', $username, 'ok', ['ip' => \LabPress\Guard::clientIp()]);
        echo json_encode(['success' => true, 'permissions' => $_SESSION['permissions']]);
    } else {
        \LabPress\Guard::recordLoginFailure($username);
        \LabPress\Guard::audit('login_failed', $username, 'denied', ['ip' => \LabPress\Guard::clientIp()]);
        echo json_encode(['success' => false, 'error' => '用户名或密码错误']);
    }
} elseif ($action === 'logout') {
    \LabPress\Guard::audit('logout', \LabPress\Guard::user(), 'ok');
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', [
            'expires' => time() - 42000,
            'path' => $params['path'] ?: '/',
            'domain' => $params['domain'] ?? '',
            'secure' => (bool)($params['secure'] ?? false),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }
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