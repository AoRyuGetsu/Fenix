<?php

declare(strict_types=1);

if (!function_exists('getError')) {
    function getError(string $code): string
    {
        static $errors = null;
        if ($errors === null) {
            $errors = require __DIR__ . '/../../config/errors.php';
        }
        return $errors[$code] ?? "Error desconocido ({$code})";
    }
}
