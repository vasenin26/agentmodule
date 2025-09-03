<?php

namespace Anymodule\Agentmodule\Services\ApiService;

use Anymodule\Agentmodule\Services\ApiService\Request\RequestInterface;

class ApiClient
{
    public function __construct(
        private string $host,
    )
    {
    }

    public function call(RequestInterface $request): Response
    {
        $client = new \GuzzleHttp\Client( ['http_errors' => false]);
        
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
        
        $response = $client->request($request->getMethod(), $this->getURI($request), $options);

        return new Response($response->getStatusCode(), $response->getBody());
    }

    private function getURI(RequestInterface $request): string
    {
        return $this->host . '/' . $request->getUrl();
    }
}