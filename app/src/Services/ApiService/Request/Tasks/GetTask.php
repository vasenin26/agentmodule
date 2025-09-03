<?php

namespace Anymodule\Agentmodule\Services\ApiService\Request\Tasks;

use Anymodule\Agentmodule\Services\ApiService\ApiClient;
use Anymodule\Agentmodule\Services\ApiService\Request\RequestInterface;

final readonly class GetTask implements RequestInterface
{
    public function __construct(
        private int $taskId
    )
    {
    }

    public function getMethod(): string
    {
        return 'GET';
    }

    public function getUrl(): string
    {
        return "task/{$this->taskId}";
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