<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
if (!hasPermission('users')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => '无权限']);
    exit;
}

header('Content-Type: application/json; charset=utf-8');
lp_guard_write_request();
$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true) ?: [];

switch ($method) {
    case 'GET':
        $users = getUsers();
        echo json_encode(['success' => true, 'users' => $users]);
        break;

    case 'POST':
        // 新建账号属于管理员操作
        if (!hasPermission('all')) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => '仅管理员可新建账号']);
            exit;
        }
        $username = trim((string)($input['username'] ?? ''));
        $password = (string)($input['password'] ?? '');
        $permissions = $input['permissions'] ?? [];
        if ($username === '' || $password === '') {
            echo json_encode(['success' => false, 'error' => '用户名和密码不能为空']);
            exit;
        }
        if (!preg_match('/^[A-Za-z0-9_\-\.]{3,32}$/', $username)) {
            echo json_encode(['success' => false, 'error' => '用户名格式不合法（3-32 位字母、数字、下划线）']);
            exit;
        }
        $exists = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
        $exists->execute([$username]);
        if ((int)$exists->fetchColumn() > 0) {
            echo json_encode(['success' => false, 'error' => '用户名已存在']);
            exit;
        }
        $hash = password_hash($password, PASSWORD_BCRYPT);
        $stmt = $pdo->prepare("INSERT INTO users (username, password_hash, permissions) VALUES (?, ?, ?)");
        $stmt->execute([$username, $hash, json_encode($permissions)]);
        echo json_encode(['success' => true]);
        break;

    case 'PUT':
        $targetUsername = $input['username'] ?? null;
        $newUsername    = $input['new_username'] ?? $targetUsername;
        $permissions    = $input['permissions'] ?? null;
        $password       = $input['password'] ?? null;

        if (empty($targetUsername)) {
            echo json_encode(['success' => false, 'error' => '缺少目标用户名']);
            exit;
        }

        $isSelf = (isset($_SESSION['user']) && $_SESSION['user'] === $targetUsername);
        $isAdmin = hasPermission('all');

        // 权限控制
        if (!$isSelf && !$isAdmin) {
            echo json_encode(['success' => false, 'error' => '无权修改其他用户']);
            exit;
        }

        // 修改密码：自己可以修改，管理员也可以修改别人
        if ($password !== null) {
            // 如果非管理员且修改的不是自己，不允许（前面已经拦截，这里再确认一次）
            if (!$isSelf && !$isAdmin) {
                echo json_encode(['success' => false, 'error' => '无权修改他人密码']);
                exit;
            }
            $hash = password_hash($password, PASSWORD_BCRYPT);
            $stmt = $pdo->prepare("UPDATE users SET password_hash = ? WHERE username = ?");
            $stmt->execute([$hash, $targetUsername]);
            echo json_encode(['success' => true]);
            break;
        }

        // 修改用户名或权限（仅管理员可操作）
        if (!$isAdmin) {
            echo json_encode(['success' => false, 'error' => '无权限修改用户信息']);
            exit;
        }

        if ($newUsername !== $targetUsername || $permissions !== null) {
            if ($newUsername !== $targetUsername) {
                $stmt = $pdo->prepare("UPDATE users SET username = ?, permissions = ? WHERE username = ?");
                $stmt->execute([$newUsername, json_encode($permissions ?? []), $targetUsername]);
            } else {
                $stmt = $pdo->prepare("UPDATE users SET permissions = ? WHERE username = ?");
                $stmt->execute([json_encode($permissions ?? []), $targetUsername]);
            }
        }
        echo json_encode(['success' => true]);
        break;

    case 'DELETE':
        // 删除账号只能由管理员执行，且不能删除自己
        if (!hasPermission('all')) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => '仅管理员可删除账号']);
            exit;
        }
        $username = (string)($input['username'] ?? '');
        if ($username === '') {
            echo json_encode(['success' => false, 'error' => '缺少用户名']);
            exit;
        }
        if (isset($_SESSION['user']) && $_SESSION['user'] === $username) {
            echo json_encode(['success' => false, 'error' => '不能删除当前登录账号']);
            exit;
        }
        $stmt = $pdo->prepare("DELETE FROM users WHERE username = ?");
        $stmt->execute([$username]);
        echo json_encode(['success' => true]);
        break;

    default:
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
}