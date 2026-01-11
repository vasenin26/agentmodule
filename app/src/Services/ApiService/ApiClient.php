<?php

namespace Anymodule\Agentmodule\Services\ApiService;

use Anymodule\Agentmodule\Application\Logger\Log;
use Anymodule\Agentmodule\Services\ApiService\Exception\RequestException;
use Anymodule\Agentmodule\Services\ApiService\Request\RequestInterface;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException as GuzzleRequestException;

class ApiClient
{
    public function __construct(
        private string $host,
    )
    {
    }

    public function call(RequestInterface $request): Response
    {
        $client = new \GuzzleHttp\Client(['http_errors' => false, 'timeout' => 30]);
        
        $options = [];
        
        // Добавляем payload для POST/PUT запросов
        if (in_array($request->getMethod(), ['POST', 'PUT', 'PATCH'])) {
            $options['json'] = $request->getPayload();
        }

        $options['headers']['Content-Type'] = 'application/json';
        $options['headers']['Accept'] = 'application/json';
        
        // Добавляем токен авторизации если есть
        if ($request->getToken()) {
            $options['headers']['Authorization'] = 'Bearer ' . $request->getToken();
        }

        $uri = $this->getURI($request);
        $method = $request->getMethod();

        try {
            $response = $client->request($method, $uri, $options);

            // Логируем ошибочные статус коды
            if ($response->getStatusCode() >= 400) {
                $responseBody = (string)$response->getBody();
                
                Log::networkError(
                    method: $method,
                    url: $uri,
                    statusCode: $response->getStatusCode(),
                    errorMessage: "HTTP error response",
                    context: [
                        'response_body' => mb_substr($responseBody, 0, 500), // Первые 500 символов
                        'headers' => $this->sanitizeHeaders($options['headers'] ?? []),
                        'payload' => $this->sanitizePayload($options['json'] ?? []),
                    ]
                );
            }

            return new Response($response->getStatusCode(), $response->getBody());

        } catch (ConnectException $e) {
            // Ошибка соединения (timeout, DNS, connection refused и т.д.)
            Log::networkError(
                method: $method,
                url: $uri,
                statusCode: null,
                errorMessage: sprintf('Connection error: %s', $e->getMessage()),
                context: [
                    'error_type' => 'connection_error',
                    'headers' => $this->sanitizeHeaders($options['headers'] ?? []),
                    'payload' => $this->sanitizePayload($options['json'] ?? []),
                    'handlerContext' => $e->getHandlerContext(),
                ]
            );

            throw new RequestException($method, $uri, 'Connection failed: ' . $e->getMessage());

        } catch (GuzzleRequestException $e) {
            // Другие HTTP ошибки от Guzzle
            $statusCode = $e->hasResponse() ? $e->getResponse()->getStatusCode() : null;
            $responseBody = $e->hasResponse() ? (string)$e->getResponse()->getBody() : 'No response';

            Log::networkError(
                method: $method,
                url: $uri,
                statusCode: $statusCode,
                errorMessage: sprintf('Request error: %s', $e->getMessage()),
                context: [
                    'error_type' => 'request_error',
                    'response_body' => mb_substr($responseBody, 0, 500),
                    'headers' => $this->sanitizeHeaders($options['headers'] ?? []),
                    'payload' => $this->sanitizePayload($options['json'] ?? []),
                ]
            );

            throw new RequestException($method, $uri, 'Request failed: ' . $e->getMessage());

        } catch (\Throwable $e) {
            // Любые другие неожиданные ошибки
            Log::exception(
                exception: $e,
                context: 'ApiClient::call',
                additionalData: [
                    'method' => $method,
                    'url' => $uri,
                    'headers' => $this->sanitizeHeaders($options['headers'] ?? []),
                ]
            );

            throw new RequestException($method, $uri, 'Unexpected error: ' . $e->getMessage());
        }
    }

    private function getURI(RequestInterface $request): string
    {
        return $this->host . '/' . $request->getUrl();
    }

    /**
     * Удаляет чувствительные данные из заголовков для безопасного логирования
     */
    private function sanitizeHeaders(array $headers): array
    {
        if (isset($headers['Authorization'])) {
            $headers['Authorization'] = '***MASKED***';
        }
        return $headers;
    }

    /**
     * Удаляет чувствительные данные из payload для безопасного логирования
     */
    private function sanitizePayload(array $payload): array
    {
        $sensitiveFields = ['token', 'password', 'api_key', 'secret', 'authorization'];
        
        foreach ($sensitiveFields as $field) {
            if (isset($payload[$field])) {
                $payload[$field] = '***MASKED***';
            }
        }
        
        return $payload;
    }
}