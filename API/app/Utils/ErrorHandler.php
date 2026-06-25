<?php

declare(strict_types=1);

namespace App\Utils;

use Symfony\Component\ErrorHandler\Debug;
use Symfony\Component\ErrorHandler\ErrorHandler as SymfonyErrorHandler;

class ErrorHandler
{
    public static function register(bool $debug = false): void
    {
        if ($debug) {
            Debug::enable();
        }

        $handler = SymfonyErrorHandler::register();
        $handler->setDefaultLogger(Logger::getInstance());
    }
}
