<?php
declare(strict_types=1);

namespace LabPress\Support;

use Throwable;

/**
 * 轻量文件日志。共享主机不依赖 Monolog/Composer。
 *
 * 日志目录默认 storage/logs；权限不足时回退 error_log()，不抛异常。
 */
final class Logger
{
    private static ?string $requestId = null;

    public static function requestId(): string
    {
        if (self::$requestId === null) {
            self::$requestId = bin2hex(random_bytes(8));
        }
        return self::$requestId;
    }

    /**
     * @param array<string,mixed> $context
     */
    public static function write(string $channel, string $level, string $message, array $context = []): void
    {
        $context = self::sanitize($context);
        $context['request_id'] = self::requestId();
        $record = [
            'ts' => date('c'),
            'level' => $level,
            'channel' => $channel,
            'message' => $message,
            'context' => $context,
        ];
        $line = json_encode($record, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($line === false) {
            $line = '{"level":"' . $level . '","message":"log_encode_failed"}';
        }

        $dir = self::logDir();
        $file = $dir . '/' . $channel . '-' . date('Ymd') . '.log';
        if ($dir !== '' && @file_put_contents($file, $line . PHP_EOL, FILE_APPEND | LOCK_EX) !== false) {
            return;
        }
        error_log('[LabPress][' . $channel . '][' . $level . '] ' . $message . ' ' . $line);
    }

    public static function info(string $message, array $context = []): void
    {
        self::write('app', 'info', $message, $context);
    }

    public static function warning(string $message, array $context = []): void
    {
        self::write('app', 'warning', $message, $context);
    }

    public static function error(string $message, array $context = []): void
    {
        self::write('app', 'error', $message, $context);
    }

    public static function exception(Throwable $e, array $context = []): void
    {
        $context['exception'] = get_class($e);
        $context['file'] = $e->getFile();
        $context['line'] = $e->getLine();
        $context['trace'] = $e->getTraceAsString();
        self::write('app', 'error', $e->getMessage(), $context);
    }

    /**
     * 审计日志：管理员/插件发布等敏感动作单独一个通道。
     */
    public static function audit(string $action, string $target, string $result = 'ok', array $context = []): void
    {
        $context['action'] = $action;
        $context['target'] = $target;
        $context['result'] = $result;
        $context['actor'] = $_SESSION['user'] ?? 'guest';
        self::write('audit', $result === 'ok' ? 'info' : 'warning', $action, $context);
    }

    public static function logDir(): string
    {
        if (defined('LABPRESS_ROOT')) {
            $dir = LABPRESS_ROOT . '/storage/logs';
        } else {
            $dir = dirname(__DIR__, 2) . '/storage/logs';
        }
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
        return is_dir($dir) && is_writable($dir) ? $dir : '';
    }

    /**
     * 避免把密码、token、Cookie 写进日志。
     *
     * @param array<string,mixed> $context
     * @return array<string,mixed>
     */
    private static function sanitize(array $context): array
    {
        $blocked = ['password', 'passwd', 'secret', 'token', 'authorization', 'cookie', 'csrf'];
        foreach ($context as $key => $value) {
            $lower = strtolower((string)$key);
            foreach ($blocked as $needle) {
                if (strpos($lower, $needle) !== false) {
                    $context[$key] = '[redacted]';
                    continue 2;
                }
            }
            if (is_array($value)) {
                $context[$key] = self::sanitize($value);
            }
        }
        return $context;
    }
}
