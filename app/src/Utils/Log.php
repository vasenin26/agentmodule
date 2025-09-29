<?php

namespace Anymodule\Agentmodule\Utils;


use Monolog\Handler\StreamHandler;
use Monolog\Level;
use Monolog\Logger;

class Log
{
    private static ?Logger $logger = null;

    private static function init(): void
    {
        static::$logger = new Logger('main_logger');
        static::$logger->pushHandler(new StreamHandler('php://stdout', Level::Debug));
    }
    private static function log(Level $level, string $message, ...$args): void
    {
        if(static::$logger === null) {
            static::init();
        }

        static::$logger->log($level, $message, $args);
    }

    public static function info(string $message, ...$args): void
    {
        self::log(Level::Info, $message, $args);
    }

    public static function notice(string $message): void
    {
        self::log(Level::Notice, $message);
    }

    public static function warning(string $getMessage, ...$args): void
    {
        self::log(Level::Warning, $getMessage, $args);
    }

    public static function storeMessages(array $messages): void
    {
        $logDir = '/app/logs';

        if (!is_dir($logDir)) {
            @mkdir($logDir, 0777, true);
        }
        $filename = $logDir . '/chat_' . date('Ymd_His') . '_' . uniqid() . '.json';
        @file_put_contents($filename, json_encode($messages, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }
}