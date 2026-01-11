<?php

namespace Anymodule\Agentmodule\Application\Logger;


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

    public static function error(string $message, ...$args): void
    {
        self::log(Level::Error, $message, $args);
    }

    /**
     * Логирование сетевых ошибок с детальной информацией
     * 
     * @param string $method HTTP метод (GET, POST, PUT, DELETE и т.д.)
     * @param string $url URL запроса
     * @param int|null $statusCode HTTP статус код ответа (если доступен)
     * @param string $errorMessage Сообщение об ошибке
     * @param array $context Дополнительный контекст (headers, payload, response body и т.д.)
     */
    public static function networkError(
        string $method,
        string $url,
        ?int $statusCode,
        string $errorMessage,
        array $context = []
    ): void {
        $logData = [
            'type' => 'NETWORK_ERROR',
            'method' => $method,
            'url' => $url,
            'status_code' => $statusCode,
            'error' => $errorMessage,
            'timestamp' => date('Y-m-d H:i:s'),
        ];

        // Добавляем дополнительный контекст, если он есть
        if (!empty($context)) {
            // Маскируем чувствительные данные
            if (isset($context['headers']['Authorization'])) {
                $context['headers']['Authorization'] = '***MASKED***';
            }
            if (isset($context['payload']['token'])) {
                $context['payload']['token'] = '***MASKED***';
            }
            if (isset($context['payload']['password'])) {
                $context['payload']['password'] = '***MASKED***';
            }

            $logData['context'] = $context;
        }

        $message = sprintf(
            'Network error: %s %s [Status: %s] - %s',
            $method,
            $url,
            $statusCode ?? 'N/A',
            $errorMessage
        );

        self::log(Level::Error, $message, $logData);
    }

    /**
     * Логирование исключений с полным контекстом
     * 
     * @param \Throwable $exception Исключение для логирования
     * @param string $context Контекст, в котором произошло исключение
     * @param array $additionalData Дополнительные данные для логирования
     */
    public static function exception(
        \Throwable $exception,
        string $context = '',
        array $additionalData = []
    ): void {
        $logData = [
            'exception_class' => get_class($exception),
            'message' => $exception->getMessage(),
            'code' => $exception->getCode(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString(),
        ];

        if (!empty($additionalData)) {
            $logData['additional_data'] = $additionalData;
        }

        if ($exception->getPrevious()) {
            $logData['previous_exception'] = [
                'class' => get_class($exception->getPrevious()),
                'message' => $exception->getPrevious()->getMessage(),
            ];
        }

        $message = $context
            ? sprintf('%s: %s - %s', $context, get_class($exception), $exception->getMessage())
            : sprintf('%s: %s', get_class($exception), $exception->getMessage());

        self::log(Level::Error, $message, $logData);
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