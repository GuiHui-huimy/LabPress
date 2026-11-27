<?php
declare(strict_types=1);

namespace LabPress;

use LabPress\Http\Response;
use LabPress\Support\ErrorCode;
use LabPress\Support\Logger;

/**
 * 登录、权限、CSRF、同源检查与审计的统一点。
 *
 * 现有全局函数 lp_guard_write_request()/hasPermission() 仍保留，作为兼容包装。
 */
final class Guard
{
    public static function user(): string
    {
        return (string)($_SESSION['user'] ?? '');
    }

    public static function isLoggedIn(): bool
    {
        return self::user() !== '';
    }

    /**
     * @return array<int,string>
     */
    public static function permissions(): array
    {
        $permissions = $_SESSION['permissions'] ?? [];
        if (is_string($permissions)) {
            $decoded = json_decode($permissions, true);
            $permissions = is_array($decoded) ? $decoded : [];
        }
        return is_array($permissions) ? array_values(array_map('strval', $permissions)) : [];
    }

    public static function hasPermission(string $permission): bool
    {
        if ($permission === '' || $permission === 'all') {
            if ($permission === 'all') {
                return in_array('all', self::permissions(), true);
            }
            return true;
        }
        $permissions = self::permissions();
        return in_array('all', $permissions, true) || in_array($permission, $permissions, true);
    }

    private const LOGIN_WINDOW_SECONDS = 300;
    private const LOGIN_MAX_ATTEMPTS = 5;

    private static function loginBucketKey(string $username): string
    {
        return 'lp_login_' . hash('sha256', strtolower(trim($username)));
    }

    /**
     * 简单的会话级登录限速：同一用户名 5 分钟内最多 5 次失败。
     * 这是第一道闸，不替代后续的 IP/账号级限速中间件。
     */
    public static function loginAllowed(string $username): bool
    {
        $key = self::loginBucketKey($username);
        $bucket = $_SESSION[$key] ?? null;
        if (!is_array($bucket) || empty($bucket['first']) || empty($bucket['count'])) {
            return true;
        }
        if ((time() - (int)$bucket['first']) > self::LOGIN_WINDOW_SECONDS) {
            unset($_SESSION[$key]);
            return true;
        }
        return (int)$bucket['count'] < self::LOGIN_MAX_ATTEMPTS;
    }

    public static function recordLoginFailure(string $username): void
    {
        $key = self::loginBucketKey($username);
        $bucket = $_SESSION[$key] ?? null;
        if (!is_array($bucket) || empty($bucket['first']) || (time() - (int)$bucket['first']) > self::LOGIN_WINDOW_SECONDS) {
            $_SESSION[$key] = ['first' => time(), 'count' => 1];
            return;
        }
        $_SESSION[$key]['count'] = (int)$bucket['count'] + 1;
    }

    public static function clearLoginFailures(string $username): void
    {
        unset($_SESSION[self::loginBucketKey($username)]);
    }

    public static function clientIp(): string
    {
        return (string)($_SERVER['REMOTE_ADDR'] ?? '');
    }

    private static function message(string $key, string $fallback): string
    {
        $text = function_exists('__') ? __($key, 'admin') : $fallback;
        return htmlspecialchars((string)$text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    public static function requireLogin(bool $json = false): bool
    {
        if (self::isLoggedIn()) {
            return true;
        }
        if ($json) {
            Response::error(ErrorCode::E_AUTH, '未登录', [], 401);
        }
        http_response_code(403);
        echo '<div class="card"><p>' . self::message('no_permission', 'No permission') . '</p></div>';
        return false;
    }

    public static function requirePermission(string $permission, bool $json = false): bool
    {
        if (self::hasPermission($permission)) {
            return true;
        }
        Logger::audit('permission_denied', $permission, 'denied');
        if ($json) {
            Response::error(ErrorCode::E_FORBIDDEN, '无权限', ['permission' => $permission], 403);
        }
        echo '<div class="card"><p>' . self::message('no_permission', 'No permission') . '</p></div>';
        return false;
    }

    public static function csrfToken(): string
    {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return (string)$_SESSION['csrf_token'];
    }

    public static function verifyCsrf($token): bool
    {
        return is_string($token)
            && !empty($_SESSION['csrf_token'])
            && hash_equals((string)$_SESSION['csrf_token'], $token);
    }

    public static function isWriteRequest(): bool
    {
        return in_array($_SERVER['REQUEST_METHOD'] ?? 'GET', ['POST', 'PUT', 'DELETE', 'PATCH'], true);
    }

    public static function isSameOrigin(): bool
    {
        $host = $_SERVER['HTTP_HOST'] ?? '';
        if ($host === '') {
            return true;
        }
        $hostNoPort = preg_replace('/:\d+$/', '', $host);
        foreach (['HTTP_ORIGIN', 'HTTP_REFERER'] as $key) {
            if (empty($_SERVER[$key])) {
                continue;
            }
            $originHost = parse_url((string)$_SERVER[$key], PHP_URL_HOST);
            if (!$originHost) {
                continue;
            }
            if (strcasecmp($originHost, (string)$hostNoPort) !== 0) {
                return false;
            }
        }
        return true;
    }

    /**
     * 写请求守卫：带 token 则必须匹配；不带 token 则必须同源。
     * 兼容 v1.1.1 的 JSON API 调用方式。
     */
    public static function guardWriteRequest(): bool
    {
        if (!self::isWriteRequest()) {
            return true;
        }
        $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? ($_POST['_csrf'] ?? null);
        if ($token !== null) {
            if (self::verifyCsrf($token)) {
                return true;
            }
            Response::error(ErrorCode::E_FORBIDDEN, 'Invalid CSRF token', [], 403);
        }
        if (!self::isSameOrigin()) {
            Response::error(ErrorCode::E_FORBIDDEN, 'Invalid request origin', [], 403);
        }
        return true;
    }

    public static function audit(string $action, string $target, string $result = 'ok', array $context = []): void
    {
        Logger::audit($action, $target, $result, $context);
    }
}
