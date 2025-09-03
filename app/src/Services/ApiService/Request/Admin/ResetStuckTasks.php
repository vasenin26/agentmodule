<?php

namespace Anymodule\Agentmodule\Services\ApiService\Request\Admin;

use Anymodule\Agentmodule\Services\ApiService\ApiClient;
use Anymodule\Agentmodule\Services\ApiService\Request\RequestInterface;
use Anymodule\Agentmodule\Services\ApiService\Response\ResponseInterface;
use Anymodule\Agentmodule\Services\ApiService\Response\Admin\ResetStuckTasksDTO;

final readonly class ResetStuckTasks implements RequestInterface
{
    public function __construct(
        private string $authToken
    )
    {
    }

    public function getMethod(): string
    {
        return 'POST';
    }

    public function getUrl(): string
    {
        return 'admin/agent/reset-stuck';
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
        
        return new ResetStuckTasksDTO(
            resetCount: $data['reset_count'] ?? 0
        );
    }
}
