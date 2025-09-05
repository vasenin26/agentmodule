<?php

namespace Anymodule\Agentmodule\Services\ApiService\Request\Pages;

use Anymodule\Agentmodule\Services\ApiService\ApiClient;
use Anymodule\Agentmodule\Services\ApiService\Request\RequestInterface;
use Anymodule\Agentmodule\Services\ApiService\Response\ResponseInterface;
use Anymodule\Agentmodule\Services\ApiService\Response\Pages\TaskHistoryDTO;
use Anymodule\Agentmodule\Services\ApiService\Response\Pages\CreatorDTO;
use Anymodule\Agentmodule\Services\ApiService\Response\Pages\TechplaneDTO;
use Anymodule\Agentmodule\Services\ApiService\Response\Pages\TaskHistoryResponse;

final readonly class GetTaskHistory implements RequestInterface
{
    public function __construct(
        private int $pageId,
        private string $token
    )
    {
    }

    public function getMethod(): string
    {
        return 'GET';
    }

    public function getUrl(): string
    {
        return "page/{$this->pageId}/tasks";
    }

    public function getPayload(): array
    {
        return [];
    }

    public function getToken(): ?string
    {
        return $this->token;
    }

    public function exec(ApiClient $client): ResponseInterface
    {
        $response = $client->call($this);
        $data = $response->getData();

        $tasks = array_map(
            fn($task) => new TaskHistoryDTO(
                id: $task['id'] ?? 0,
                status: $task['status'] ?? '',
                created_at: $task['created_at'] ?? '',
                updated_at: $task['updated_at'] ?? '',
                creator: isset($task['creator']) 
                    ? new CreatorDTO(
                        id: $task['creator']['id'] ?? 0,
                        name: $task['creator']['name'] ?? '',
                        email: $task['creator']['email'] ?? ''
                    )
                    : null,
                techplane: isset($task['techplane'])
                    ? new TechplaneDTO(
                        id: $task['techplane']['id'] ?? 0,
                        title: $task['techplane']['title'] ?? ''
                    )
                    : null
            ),
            $data
        );

        return new TaskHistoryResponse($tasks);
    }
}