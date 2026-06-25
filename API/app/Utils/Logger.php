<?php

declare(strict_types=1);

namespace App\Utils;

use Monolog\Level;
use Monolog\Logger as MonologLogger;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\RotatingFileHandler;

class Logger
{
    private static ?MonologLogger $instance = null;

    public static function getInstance(string $channel = 'app'): MonologLogger
    {
        if (self::$instance !== null) {
            return self::$instance;
        }

        $logger = new MonologLogger($channel);

        $logPath = __DIR__ . '/../../var/log';
        if (!is_dir($logPath)) {
            mkdir($logPath, 0775, true);
        }

        $logger->pushHandler(new RotatingFileHandler(
            $logPath . '/app.log',
            30,
            Level::Debug
        ));

        return self::$instance = $logger;
    }
}
