<?php

namespace Anymodule\Agentmodule\Services\ApiService\Request\Tasks;

use Anymodule\Agentmodule\Services\ApiService\ApiClient;
use Anymodule\Agentmodule\Services\ApiService\Exception\RequestException;
use Anymodule\Agentmodule\Services\ApiService\Request\RequestInterface;
use Anymodule\Agentmodule\Services\ApiService\Response\ResponseInterface;
use Anymodule\Agentmodule\Services\ApiService\Response\Tasks\UpdateTaskDTO;
use Anymodule\Agentmodule\Services\ApiService\Request\Validator\AgentApiValidator;

final readonly class ProcessingAgentTask implements RequestInterface
{
    public function __construct(
        private string $token,
        private int $taskId,
        private string $agentId,
    )
    {
    }

    public function getMethod(): string
    {
        return 'PUT';
    }

    public function getUrl(): string
    {
        return "agent/task/{$this->taskId}/process";
    }

    public function getPayload(): array
    {
        return [
            'agent_uuid' => $this->agentId,
        ];
    }

    public function getToken(): ?string
    {
        return $this->token;
    }

    public function exec(ApiClient $client): ResponseInterface
    {
        $response = $client->call($this);
        $data = $response->getData();
        
        if ($response->code !== 200) {
            throw new RequestException(
                $this->getMethod(),
                $this->getUrl(),
                $response->getError()
            );
        }
        
        return new UpdateTaskDTO(
            status: $data['status'] ?? '',
            message: $data['message'] ?? ''
        );
    }
}
