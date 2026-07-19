<?php

declare(strict_types=1);

namespace App\Core;

class AppException extends \RuntimeException
{
    public function __construct(
        private readonly string $errorCode,
        ?\Throwable $previous = null,
    ) {
        parent::__construct(getError($errorCode), self::inferHttpStatus($errorCode), $previous);
    }

    public function getErrorCode(): string
    {
        return $this->errorCode;
    }

    private static function inferHttpStatus(string $code): int
    {
        return match (true) {
            str_ends_with($code, '_NOT_FOUND'),
            $code === 'NOT_FOUND'                                   => 404,
            $code === 'INVALID_CREDENTIALS',
            str_starts_with($code, 'TOKEN_'),
            $code === 'INVALID_AUTH_HEADER'                         => 401,
            $code === 'USER_DEACTIVATED',
            $code === 'FORBIDDEN'                                   => 403,
            str_ends_with($code, '_EXISTS'),
            str_ends_with($code, '_OVERLAP'),
            $code === 'USER_NOT_ASSOCIATED',
            $code === 'CONFLICT'                                    => 409,
            $code === 'METHOD_NOT_ALLOWED'                          => 405,
            $code === 'INTERNAL_ERROR'                              => 500,
            $code === 'VALIDATION_JSON_INVALID'                     => 400,
            $code === 'PASSWORD_REQUIRED',
            $code === 'PERIOD_DATES_INVALID',
            $code === 'INVALID_QUESTION_TYPE',
            $code === 'INVALID_TYPE',
            str_starts_with($code, 'VALIDATION_')                   => 422,
            default                                                 => 400,
        };
    }
}
