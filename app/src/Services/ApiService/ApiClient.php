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
        $client = new \GuzzleHttp\Client();
        $response = $client->request($request->getMethod(), $this->getURI($request));

        return new Response($response->getBody());
    }

    private function getURI(RequestInterface $request): string
    {
        return $this->host . '/' . $request->getUrl();
    }
}