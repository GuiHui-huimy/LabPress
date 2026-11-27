<?php
require_once __DIR__ . '/../includes/config.php';
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
header('Location: login.php');
exit;