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
    private static function log(Level $level, string $message): void
    {
        if(static::$logger === null) {
            static::init();
        }

        static::$logger->log($level, $message);
    }

    public static function info(string $message): void
    {
        self::log(Level::Info, $message);
    }

    public static function notice(string $message): void
    {
        self::log(Level::Notice, $message);
    }
}