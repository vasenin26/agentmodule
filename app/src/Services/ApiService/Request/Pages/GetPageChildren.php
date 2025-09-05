<?php

namespace Anymodule\Agentmodule\Services\ApiService\Request\Pages;

use Anymodule\Agentmodule\Services\ApiService\ApiClient;
use Anymodule\Agentmodule\Services\ApiService\Request\RequestInterface;
use Anymodule\Agentmodule\Services\ApiService\Response\ResponseInterface;
use Anymodule\Agentmodule\Services\ApiService\Response\Pages\PageListDTO;
use Anymodule\Agentmodule\Services\ApiService\Response\Pages\PageChildrenResponse;

final readonly class GetPageChildren implements RequestInterface
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
        return "page/{$this->pageId}/children";
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

        $children = array_map(
            fn($child) => new PageListDTO(
                id: $child['id'] ?? 0,
                title: $child['title'] ?? ''
            ),
            $data
        );

        return new PageChildrenResponse($children);
    }
}
