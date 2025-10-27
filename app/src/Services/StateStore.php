<?php

namespace Anymodule\Agentmodule\Services;

use Anymodule\Agentmodule\Interface\Storage\StateStoreInterface;
use Anymodule\Agentmodule\Utils\Log;

/**
 * Клиент для работы с сервером состояния
 */
class StateStore implements StateStoreInterface
{
    private string $serverUrl;
    private string $stopUrl;
    private bool $autoStop;
    private static ?int $serverPid = null;

    /**
     * Приватный конструктор - использовать StateStore::run() для создания экземпляра
     * 
     * @param string $host Хост сервера состояния (например: localhost)
     * @param int $port Порт сервера состояния (например: 8080)
     * @param bool $autoStop Автоматически останавливать сервер при завершении процесса
     */
    private function __construct(string $host = 'localhost', int $port = 8080, bool $autoStop = true)
    {
        $this->serverUrl = "http://{$host}:{$port}/state";
        $this->stopUrl = "http://{$host}:{$port}/stop";
        $this->autoStop = $autoStop;
    }

    /**
     * Запускает сервер состояния и возвращает клиент для работы с ним
     * 
     * @param string $host Хост для запуска сервера (по умолчанию: 0.0.0.0)
     * @param int $port Порт для запуска сервера (по умолчанию: 8484)
     * @param bool $autoStop Автоматически останавливать сервер при завершении процесса
     * @return self Экземпляр клиента для работы с сервером
     * @throws \RuntimeException Если не удалось запустить сервер
     */
    public static function run(string $host = '0.0.0.0', int $port = 8484, bool $autoStop = true): self
    {
        // Запускаем сервер в фоне с правильным перенаправлением вывода
        $command = sprintf(
            'php -r "require \"/app/vendor/autoload.php\"; (new \Anymodule\Agentmodule\State(\"%s\", %d))->run();" > /dev/null 2>&1 &',
            $host,
            $port
        );

        exec($command);

        // Даем серверу время на запуск
        usleep(3000000); // 3000ms

        // Создаем клиент
        $client = new self($host, $port, $autoStop);
        
        // Проверяем, что сервер отвечает
        $maxAttempts = 20;
        for ($i = 0; $i < $maxAttempts; $i++) {
            try {
                $client->pull();
                // Если запрос прошел успешно, сервер запущен
                Log::info("Metric server started on {$host}:{$port}");
                return $client;
            } catch (\RuntimeException $e) {
                if ($i < $maxAttempts - 1) {
                    usleep(200000); // Ждем еще 200ms
                }
            }
        }

        throw new \RuntimeException("State server failed to start or not responding on {$host}:{$port}");
    }

    /**
     * Деструктор - автоматически останавливает сервер при завершении процесса
     */
    public function __destruct()
    {
        if ($this->autoStop) {
            try {
                $this->stop();
            } catch (\RuntimeException $e) {
                // Игнорируем ошибки при остановке в деструкторе
                // Сервер может быть уже остановлен
            }
        }
    }

    /**
     * Отправляет данные на сервер состояния
     * 
     * @param string $key Ключ для сохранения
     * @param mixed $value Значение для сохранения
     * @return void
     * @throws \RuntimeException Если не удалось отправить данные
     */
    public function push(string $key, mixed $value): void
    {
        $data = json_encode([$key => $value]);

        $ch = curl_init($this->serverUrl);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($response === false || $httpCode !== 200) {
            throw new \RuntimeException(
                "Failed to push state to server: " . ($error ?: "HTTP $httpCode")
            );
        }
    }

    /**
     * Получает все данные с сервера состояния
     * 
     * @return array Все сохраненные данные
     * @throws \RuntimeException Если не удалось получить данные
     */
    public function pull(): array
    {
        $ch = curl_init($this->serverUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($response === false || $httpCode !== 200) {
            throw new \RuntimeException(
                "Failed to pull state from server: " . ($error ?: "HTTP $httpCode")
            );
        }

        $data = json_decode($response, true);

        if (!is_array($data)) {
            throw new \RuntimeException("Invalid response from state server");
        }

        return $data;
    }

    /**
     * Останавливает сервер состояния
     * 
     * @return void
     * @throws \RuntimeException Если не удалось остановить сервер
     */
    public function stop(): void
    {
        $ch = curl_init($this->stopUrl);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, '{}');
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($response === false || $httpCode !== 200) {
            throw new \RuntimeException(
                "Failed to stop server: " . ($error ?: "HTTP $httpCode")
            );
        }
    }
}
