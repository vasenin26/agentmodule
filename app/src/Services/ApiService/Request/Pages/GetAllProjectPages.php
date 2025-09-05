<?php

namespace Anymodule\Agentmodule\Services\ApiService\Request\Pages;

use Anymodule\Agentmodule\Services\ApiService\ApiClient;
use Anymodule\Agentmodule\Services\ApiService\Request\RequestInterface;
use Anymodule\Agentmodule\Services\ApiService\Response\ResponseInterface;
use Anymodule\Agentmodule\Services\ApiService\Response\Pages\PageListDTO;
use Anymodule\Agentmodule\Services\ApiService\Response\Pages\PageListResponse;

final readonly class GetAllProjectPages implements RequestInterface
{
    public function __construct(
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
        return 'pages';
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

        $pages = array_map(
            fn($page) => new PageListDTO(
                id: $page['id'] ?? 0,
                title: $page['title'] ?? ''
            ),
            $data
        );

        return new PageListResponse($pages);
    }
}
