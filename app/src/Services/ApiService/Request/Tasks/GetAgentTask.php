<?php

namespace Anymodule\Agentmodule\Services\ApiService\Request\Tasks;

use Anymodule\Agentmodule\Services\ApiService\ApiClient;
use Anymodule\Agentmodule\Services\ApiService\Request\RequestInterface;
use Anymodule\Agentmodule\Services\ApiService\Response\ResponseInterface;
use Anymodule\Agentmodule\Services\ApiService\Response\Tasks\TaskDTO;
use Anymodule\Agentmodule\Utils\Log;
use GuzzleHttp\Exception\ClientException;

final readonly class GetAgentTask implements RequestInterface
{
    public function __construct(
        private string $agentId
    )
    {
    }

    public function getMethod(): string
    {
        return 'POST';
    }

    public function getUrl(): string
    {
        return 'agent/task';
    }

    public function getPayload(): array
    {
        return [
            'agent_id' => $this->agentId
        ];
    }

    public function getToken(): ?string
    {
        return null;
    }

    public function exec(ApiClient $client): ?TaskDTO
    {
        try {
            $response = $client->call($this);

            if($response->code !== 200){
                return null;
            }

            $data = $response->getData();

            return new TaskDTO(
                task_id: $data['id'],
                project_id: $data['project_id'],
                messages: $data['chat']['messages'] ?? [],
            );
        } catch (ClientException $exception) {
            if($exception->getResponse()->getStatusCode() !== 404) {
                Log::warning($exception->getMessage());
            }
            return null;
        }

    }
}
