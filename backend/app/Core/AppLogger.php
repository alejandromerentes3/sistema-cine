<?php
namespace App\Core;

use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Processor\UidProcessor;
use Monolog\Processor\WebProcessor;

class AppLogger {
    private static $instances = [];

    public static function getLogger(string $channel = 'app'): Logger {
        if (!isset(self::$instances[$channel])) {
            $logger = new Logger($channel);
            
            // Rotating files - 7 días máximo
            $handler = new RotatingFileHandler(
                __DIR__ . '/../../logs/' . $channel . '.log',
                7,
                Logger::DEBUG
            );
            
            $logger->pushHandler($handler);
            $logger->pushProcessor(new UidProcessor());
            $logger->pushProcessor(new WebProcessor());
            
            self::$instances[$channel] = $logger;
        }
        
        return self::$instances[$channel];
    }
}
