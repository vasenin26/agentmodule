<?php

namespace Anymodule\Agentmodule\Services\ApiService\Request\Tasks;

use Anymodule\Agentmodule\Services\ApiService\ApiClient;
use Anymodule\Agentmodule\Services\ApiService\Request\RequestInterface;
use Anymodule\Agentmodule\Services\ApiService\Response\ResponseInterface;
use Anymodule\Agentmodule\Services\ApiService\Response\Tasks\TaskDetailsDTO;

final readonly class GetAgentTaskDetails implements RequestInterface
{
    public function __construct(
        private int $taskId,
        private string $agentId
    )
    {
    }

    public function getMethod(): string
    {
        return 'GET';
    }

    public function getUrl(): string
    {
        return "agent/task/{$this->taskId}?agent_id={$this->agentId}";
    }

    public function getPayload(): array
    {
        return [];
    }

    public function getToken(): ?string
    {
        return null;
    }

    public function exec(ApiClient $client): ResponseInterface
    {
        $response = $client->call($this);
        $data = $response->getData();
        
        if (isset($data['error'])) {
            throw new \RuntimeException($data['error']);
        }
        
        return new TaskDetailsDTO(
            id: $data['id'] ?? 0,
            projectId: $data['project_id'] ?? 0,
            agentId: $data['agent_id'] ?? '',
            status: $data['status'] ?? ''
        );
    }
}
