<?php

namespace Anymodule\Agentmodule\Services\ApiService\Request\Admin;

use Anymodule\Agentmodule\Services\ApiService\ApiClient;
use Anymodule\Agentmodule\Services\ApiService\Request\RequestInterface;
use Anymodule\Agentmodule\Services\ApiService\Response\ResponseInterface;
use Anymodule\Agentmodule\Services\ApiService\Response\Admin\StuckTasksDTO;

final readonly class GetStuckTasks implements RequestInterface
{
    public function __construct(
        private string $authToken
    )
    {
    }

    public function getMethod(): string
    {
        return 'GET';
    }

    public function getUrl(): string
    {
        return 'admin/agent/stuck';
    }

    public function getPayload(): array
    {
        return [];
    }

    public function getToken(): ?string
    {
        return $this->authToken;
    }

    public function exec(ApiClient $client): ResponseInterface
    {
        $response = $client->call($this);
        $data = $response->getData();
        
        return new StuckTasksDTO(
            stuckTasks: $data['stuck_tasks'] ?? []
        );
    }
}
