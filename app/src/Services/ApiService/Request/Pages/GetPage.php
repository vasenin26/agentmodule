<?php

namespace Anymodule\Agentmodule\Services\ApiService\Request\Pages;

use Anymodule\Agentmodule\Services\ApiService\ApiClient;
use Anymodule\Agentmodule\Services\ApiService\Request\RequestInterface;

class GetPage implements RequestInterface
{

    public function getMethod(): string
    {
        return 'GET';
    }

    public function getUrl(): string
    {
        return "page/{$this->pageId}";
    }

    public function getPayload(): array
    {
        return [];
    }

    public function getToken(): ?string
    {
        return null;
    }

    public function exec(ApiClient $client): string
    {
        $response = $client->call($this);

        return $response->getBody();
    }
}