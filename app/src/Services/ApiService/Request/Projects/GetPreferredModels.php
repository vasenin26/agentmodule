<?php

namespace Anymodule\Agentmodule\Services\ApiService\Request\Projects;

use Anymodule\Agentmodule\Services\ApiService\Request\RequestInterface;
use Anymodule\Agentmodule\Services\ApiService\ApiClient;
use Anymodule\Agentmodule\Services\ApiService\Response\ResponseInterface;
use Anymodule\Agentmodule\Services\ApiService\Response\Projects\PreferredModelsListDTO;

class GetPreferredModels implements RequestInterface
{
    public function __construct(
        private string $token,
        private int $projectId
    ) {
    }

    public function getMethod(): string
    {
        return 'GET';
    }

    public function getUrl(): string
    {
        return "project/{$this->projectId}/generation-models";
    }

    public function getPayload(): array
    {
        return [];
    }

    public function getToken(): ?string
    {
        return $this->token;
    }

    public function exec(ApiClient $client): ?PreferredModelsListDTO
    {
        $response = $client->call($this);

        if ($response->code === 404) {
            return null;
        }

        if ($response->code !== 200) {
            throw new \Exception("Failed to get preferred models: " . $response->getError());
        }

        $data = $response->getData();
        return PreferredModelsListDTO::fromArray($data);
    }
}
