<?php

namespace Anymodule\Agentmodule\Services\ApiService\Request\Tasks;

use Anymodule\Agentmodule\Services\ApiService\ApiClient;
use Anymodule\Agentmodule\Services\ApiService\Request\RequestInterface;

class GetTask implements RequestInterface
{

    public function getMethod(): string
    {
        return 'GET';
    }

    public function getUrl(): string
    {
        return "task/{$this->pageId}";
    }

    public function getPayload(): array
    {
        return [];
    }

    public function getToken(): ?string
    {
        return null;
    }

    public function exec(ApiClient $client): array
    {
        $response = $client->call($this);

        return $response->getData();
    }
}