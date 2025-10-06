<?php

namespace Anymodule\Agentmodule;

/**
 * Простой HTTP сервер метрик
 * Сохраняет состояние приложения в памяти и предоставляет доступ через HTTP
 */
class State
{
    private array $data = [];
    private string $host;
    private int $port;
    private bool $shouldStop = false;

    public function __construct(string $host = '0.0.0.0', int $port = 8080)
    {
        $this->host = $host;
        $this->port = $port;
    }

    /**
     * Запускает HTTP сервер
     */
    public function run(): void
    {
        $socket = stream_socket_server("tcp://{$this->host}:{$this->port}", $errno, $errstr);
        
        if (!$socket) {
            throw new \RuntimeException("Failed to create socket: $errstr ($errno)");
        }

        echo "Metrics server started on {$this->host}:{$this->port}\n";

        while (!$this->shouldStop) {
            $client = @stream_socket_accept($socket, -1);
            
            if (!$client) {
                continue;
            }

            $this->handleRequest($client);
            fclose($client);
        }

        fclose($socket);
        echo "Metrics server stopped\n";
    }

    /**
     * Обрабатывает HTTP запрос
     */
    private function handleRequest($client): void
    {
        $request = '';
        while ($line = fgets($client)) {
            $request .= $line;
            if (trim($line) === '') {
                break;
            }
        }

        $lines = explode("\n", $request);
        $firstLine = $lines[0] ?? '';
        
        preg_match('/^(\w+)\s+([^\s]+)\s+HTTP/', $firstLine, $matches);
        
        if (count($matches) < 3) {
            $this->sendResponse($client, 400, 'Bad Request');
            return;
        }

        $method = $matches[1];
        $path = $matches[2];

        // Получаем Content-Length для POST запроса
        $contentLength = 0;
        foreach ($lines as $line) {
            if (stripos($line, 'Content-Length:') === 0) {
                $contentLength = (int) trim(substr($line, 15));
                break;
            }
        }

        // Читаем тело запроса если есть Content-Length
        $body = '';
        if ($contentLength > 0) {
            $body = fread($client, $contentLength);
        }

        if ($path === '/state' && $method === 'POST') {
            $this->handlePostState($client, $body);
        } elseif ($path === '/state' && $method === 'GET') {
            $this->handleGetState($client);
        } elseif ($path === '/stop' && $method === 'POST') {
            $this->handleStop($client);
        } else {
            $this->sendResponse($client, 404, 'Not Found');
        }
    }

    /**
     * Обрабатывает POST /state
     * Сохраняет переданные поля в памяти, не удаляя ранее сохраненные
     */
    private function handlePostState($client, string $body): void
    {
        $newData = json_decode($body, true);

        if (!is_array($newData)) {
            $this->sendResponse($client, 400, 'Invalid JSON', ['error' => 'Request body must be valid JSON']);
            return;
        }

        // Объединяем новые данные с существующими (новые перезаписывают старые)
        $this->data = array_merge($this->data, $newData);

        $this->sendResponse($client, 200, 'OK', ['status' => 'saved', 'data' => $this->data]);
    }

    /**
     * Обрабатывает GET /state
     * Возвращает все сохраненные данные в виде JSON объекта
     */
    private function handleGetState($client): void
    {
        $this->sendResponse($client, 200, 'OK', $this->data);
    }

    /**
     * Обрабатывает POST /stop
     * Останавливает сервер
     */
    private function handleStop($client): void
    {
        $this->sendResponse($client, 200, 'OK', ['status' => 'stopping', 'message' => 'Server is shutting down']);
        $this->shouldStop = true;
    }

    /**
     * Отправляет HTTP ответ
     */
    private function sendResponse($client, int $code, string $status, ?array $data = null): void
    {
        $body = '';
        
        if ($data !== null) {
            $body = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        }

        $response = "HTTP/1.1 $code $status\r\n";
        $response .= "Content-Type: application/json; charset=utf-8\r\n";
        $response .= "Content-Length: " . strlen($body) . "\r\n";
        $response .= "Connection: close\r\n";
        $response .= "\r\n";
        $response .= $body;

        fwrite($client, $response);
    }
}

