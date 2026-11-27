<?php
declare(strict_types=1);

namespace LabPress\Support;

final class ErrorCode
{
    public const E_SERVER = 'E_SERVER';
    public const E_AUTH = 'E_AUTH';
    public const E_FORBIDDEN = 'E_FORBIDDEN';
    public const E_NOT_FOUND = 'E_NOT_FOUND';
    public const E_VALIDATION = 'E_VALIDATION';
    public const E_RATE_LIMIT = 'E_RATE_LIMIT';
    public const E_STORAGE = 'E_STORAGE';
    public const E_PLUGIN = 'E_PLUGIN';
    public const E_CONFLICT = 'E_CONFLICT';

    public static function httpStatus(string $code): int
    {
        switch ($code) {
            case self::E_AUTH:
                return 401;
            case self::E_FORBIDDEN:
                return 403;
            case self::E_NOT_FOUND:
                return 404;
            case self::E_VALIDATION:
            case self::E_PLUGIN:
                return 422;
            case self::E_RATE_LIMIT:
                return 429;
            case self::E_CONFLICT:
                return 409;
            case self::E_STORAGE:
                return 503;
            default:
                return 500;
        }
    }
}
