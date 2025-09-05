<?php

namespace Anymodule\Agentmodule\Services\ApiService\Request\Pages;

use Anymodule\Agentmodule\Services\ApiService\ApiClient;
use Anymodule\Agentmodule\Services\ApiService\Request\RequestInterface;
use Anymodule\Agentmodule\Services\ApiService\Response\ResponseInterface;
use Anymodule\Agentmodule\Services\ApiService\Response\Pages\PageDTO;

final readonly class GetPage implements RequestInterface
{
    public function __construct(
        private int $pageId
    )
    {
    }

    public function getMethod(): string
    {
        return 'GET';
    }

    public function getUrl(): string
    {
        return "page/{$this->pageId}";
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

        return new PageDTO(
            id: $data['id'] ?? 0,
            title: $data['title'] ?? '',
            content: $data['content'] ?? '',
            files: $data['files'] ?? []
        );
    }
}