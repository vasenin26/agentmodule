<?php

namespace Anymodule\Agentmodule\Services\ApiService\Request\Pages;

use Anymodule\Agentmodule\Services\ApiService\ApiClient;
use Anymodule\Agentmodule\Services\ApiService\Request\RequestInterface;
use Anymodule\Agentmodule\Services\ApiService\Response\ResponseInterface;
use Anymodule\Agentmodule\Services\ApiService\Response\Pages\PageDTO;
use Anymodule\Agentmodule\Services\ApiService\Response\Pages\PageParentResponse;

final readonly class GetPageParent implements RequestInterface
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
        return "page/{$this->pageId}/parent";
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
        
        // Если статус 204, возвращаем null
        if ($response->getStatusCode() === 204) {
            return new PageParentResponse(null);
        }
        
        $data = $response->getData();

        $page = new PageDTO(
            id: $data['id'] ?? 0,
            title: $data['title'] ?? '',
            content: $data['content'] ?? '',
            files: $data['files'] ?? []
        );

        return new PageParentResponse($page);
    }
}
