<?php

namespace Anymodule\Agentmodule\Services\ApiService\Request\Pages;

use Anymodule\Agentmodule\Services\ApiService\ApiClient;
use Anymodule\Agentmodule\Services\ApiService\Request\RequestInterface;
use Anymodule\Agentmodule\Services\ApiService\Response\ResponseInterface;
use Anymodule\Agentmodule\Services\ApiService\Response\Pages\PageListDTO;
use Anymodule\Agentmodule\Services\ApiService\Response\Pages\RelatedPagesResponse;

final readonly class FindRelatedPages implements RequestInterface
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
        return "agent/page/{$this->pageId}/related";
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

        $related = array_map(
            fn($page) => new PageListDTO(
                id: $page['id'] ?? 0,
                title: $page['title'] ?? ''
            ),
            $data
        );

        return new RelatedPagesResponse($related);
    }
}
