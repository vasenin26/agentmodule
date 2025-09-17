<?php

namespace Anymodule\Agentmodule\Services\ApiService\Request\Pages;

use Anymodule\Agentmodule\Services\ApiService\ApiClient;
use Anymodule\Agentmodule\Services\ApiService\Request\RequestInterface;
use Anymodule\Agentmodule\Services\ApiService\Response\Pages\PageVersionDTO;
use Anymodule\Agentmodule\Services\ApiService\Response\ResponseInterface;

final readonly class GetPageVersion implements RequestInterface
{
    public function __construct(
        private string $token,
        private string $versionId,
    )
    {
    }

    public function getMethod(): string
    {
        return 'GET';
    }

    public function getUrl(): string
    {
        return "agent/page/version/{$this->versionId}";
    }

    public function getPayload(): array
    {
        return [];
    }

    public function getToken(): ?string
    {
        return $this->token;
    }

    public function exec(ApiClient $client): PageVersionDTO
    {
        $response = $client->call($this);
        $data = $response->getData();

        return new PageVersionDTO(
            title: $data['title'] ?? '',
            content: $data['content'] ?? '',
            pageId: (int)($data['pageId'] ?? 0),
            versionId: (string)($data['versionId'] ?? ''),
            previousVersionId: isset($data['previousVersionId']) ? (string)$data['previousVersionId'] : null,
        );
    }
}


