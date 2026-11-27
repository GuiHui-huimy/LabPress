<?php
declare(strict_types=1);

namespace LabPress\Http;

use LabPress\Support\ErrorCode;
use LabPress\Support\Logger;

final class Response
{
    public static function json($data, int $status = 200): void
    {
        if (!headers_sent()) {
            http_response_code($status);
            header('Content-Type: application/json; charset=utf-8');
            header('X-Request-Id: ' . Logger::requestId());
        }
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    public static function success($data = null, array $meta = [], int $status = 200): void
    {
        self::json([
            'ok' => true,
            'data' => $data,
            'meta' => $meta,
            'request_id' => Logger::requestId(),
        ], $status);
    }

    /**
     * @param array<string,mixed> $details
     */
    public static function error(string $code, string $message, array $details = [], ?int $status = null): void
    {
        self::json([
            'ok' => false,
            'error' => [
                'code' => $code,
                'message' => $message,
                'details' => $details,
            ],
            'request_id' => Logger::requestId(),
        ], $status ?? ErrorCode::httpStatus($code));
    }
}
