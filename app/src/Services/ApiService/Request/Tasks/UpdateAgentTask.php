<?php

namespace Anymodule\Agentmodule\Services\ApiService\Request\Tasks;

use Anymodule\Agentmodule\Services\ApiService\ApiClient;
use Anymodule\Agentmodule\Services\ApiService\Request\RequestInterface;
use Anymodule\Agentmodule\Services\ApiService\Response\ResponseInterface;
use Anymodule\Agentmodule\Services\ApiService\Response\Tasks\UpdateTaskDTO;
use Anymodule\Agentmodule\Services\ApiService\Request\Validator\AgentApiValidator;

final readonly class UpdateAgentTask implements RequestInterface
{
    public function __construct(
        private int $taskId,
        private string $agentId,
        private array $chatMessages,
        private array $tokenStats,
        private ?string $result = null
    )
    {
    }

    public function getMethod(): string
    {
        return 'PUT';
    }

    public function getUrl(): string
    {
        return "agent/task/{$this->taskId}";
    }

    public function getPayload(): array
    {
        return [
            'agent_id' => $this->agentId,
            'chat' => $this->chatMessages,
            'stats' => $this->tokenStats,
            'result' => $this->result
        ];
    }

    public function getToken(): ?string
    {
        return null;
    }

    public function exec(ApiClient $client): ResponseInterface
    {
        $response = $client->call($this);
        $data = $response->getData();
        
        if ($response->code !== 200) {
            throw new \RuntimeException($response->getBody());
        }
        
        return new UpdateTaskDTO(
            status: $data['status'] ?? '',
            message: $data['message'] ?? ''
        );
    }

    /**
     * Создать сообщение для чата с валидацией
     */
    public static function createChatMessage(string $role, string $content, ?string $id = null, ?AgentApiValidator $validator = null): array
    {
        $validator = $validator ?? new AgentApiValidator();
        $validator->validateChatMessageData($role, $content, $id);
        
        $message = [
            'role' => $role,
            'content' => $content
        ];
        
        if ($id !== null) {
            $message['id'] = $id;
        }
        
        return $message;
    }

    /**
     * Создать статистику токенов с валидацией
     */
    public static function createTokenStats(?int $promptTokens = null, ?int $completionTokens = null, ?int $totalTokens = null, ?AgentApiValidator $validator = null): array
    {
        $validator = $validator ?? new AgentApiValidator();
        $validator->validateTokenStatsData($promptTokens, $completionTokens, $totalTokens);
        
        return [
            'prompt_tokens' => $promptTokens,
            'completion_tokens' => $completionTokens,
            'total_tokens' => $totalTokens
        ];
    }
}
