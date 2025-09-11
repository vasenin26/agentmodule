<?php

namespace Anymodule\Agentmodule\Services\ApiService\Request\Tasks;

use Anymodule\Agentmodule\Services\ApiService\ApiClient;
use Anymodule\Agentmodule\Services\ApiService\Exception\RequestException;
use Anymodule\Agentmodule\Services\ApiService\Request\RequestInterface;
use Anymodule\Agentmodule\Services\ApiService\Response\ResponseInterface;
use Anymodule\Agentmodule\Services\ApiService\Response\Tasks\TaskDTO;
use Anymodule\Agentmodule\Utils\Log;
use GuzzleHttp\Exception\ClientException;
use function PHPUnit\Framework\throwException;

final readonly class GetAgentTask implements RequestInterface
{
    public function __construct(
        private string $token,
        private string $agentId,
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
            'agent_uuid' => $this->agentId
        ];
    }

    public function getToken(): ?string
    {
        return $this->token;
    }

    public function exec(ApiClient $client): ?TaskDTO
    {
        $response = $client->call($this);

        if ($response->code === 404) {
            return null;
        }

        if ($response->code !== 200) {
            throw new RequestException(
                $this->getMethod(),
                $this->getUrl(),
                $response->getError()
            );
        }

        $data = $response->getData();

        return new TaskDTO(
            task_id: $data['id'],
            project_id: $data['project_id'],
            messages: $data['chat']['messages'] ?? [],
            resulRequired: $data['result_required'] ?? true,
        );

    }
}
