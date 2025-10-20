<?php

namespace Anymodule\Agentmodule\Services\ApiService\Request\Tasks;

use Anymodule\Agentmodule\Services\ApiService\ApiClient;
use Anymodule\Agentmodule\Services\ApiService\Exception\RequestException;
use Anymodule\Agentmodule\Services\ApiService\Request\RequestInterface;
use Anymodule\Agentmodule\Services\ApiService\Response\ResponseInterface;
use Anymodule\Agentmodule\Services\ApiService\Response\Tasks\CreateSubtaskDTO;
use Anymodule\Agentmodule\Services\ApiService\Request\Validator\AgentApiValidator;

final class CreateSubtask implements RequestInterface
{
    public function __construct(
        private string $token,
        private int $parentTaskId,
        private string $type,
        private string $agentUuid,
        private ?AgentApiValidator $validator = null,
    )
    {
        $validator = $validator ?? new AgentApiValidator();
        $validator->validateSubtaskData($type, $agentUuid);
    }

    public function getMethod(): string
    {
        return 'POST';
    }

    public function getUrl(): string
    {
        return "agent/task/{$this->parentTaskId}/subtasks";
    }

    public function getPayload(): array
    {
        return [
            'type' => $this->type,
            'agent_uuid' => $this->agentUuid,
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
        
        if ($response->code !== 201) {
            throw new RequestException(
                $this->getMethod(),
                $this->getUrl(),
                $response->getError()
            );
        }
        
        return new CreateSubtaskDTO(
            id: $data['id']
        );
    }

    /**
     * Создать подзадачу с валидацией данных
     */
    public static function create(
        string $token,
        int $parentTaskId,
        string $type,
        string $agentUuid,
        ?AgentApiValidator $validator = null
    ): self {
        return new self(
            token: $token,
            parentTaskId: $parentTaskId,
            type: $type,
            agentUuid: $agentUuid,
            validator: $validator
        );
    }
}
