<?php

namespace Anymodule\Agentmodule\Services\ApiService\Request\Pages;

use Anymodule\Agentmodule\Services\ApiService\ApiClient;
use Anymodule\Agentmodule\Services\ApiService\Request\RequestInterface;
use Anymodule\Agentmodule\Services\ApiService\Response\ResponseInterface;
use Anymodule\Agentmodule\Services\ApiService\Response\Pages\PageHierarchyDTO;
use Anymodule\Agentmodule\Services\ApiService\Response\Pages\PageHierarchyResponse;

final readonly class GetPageHierarchy implements RequestInterface
{
    public function __construct(
        private string $token,
        private ?int $rootPageId = null
    )
    {
    }

    public function getMethod(): string
    {
        return 'GET';
    }

    public function getUrl(): string
    {
        $url = 'pages/hierarchy';
        if ($this->rootPageId !== null) {
            $url .= "?root_page_id={$this->rootPageId}";
        }
        return $url;
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

        $hierarchy = array_map(
            fn($page) => $this->buildHierarchyDTO($page),
            $data
        );

        return new PageHierarchyResponse($hierarchy);
    }
    
    private function buildHierarchyDTO(array $page): PageHierarchyDTO
    {
        return new PageHierarchyDTO(
            id: $page['id'] ?? 0,
            title: $page['title'] ?? '',
            children: isset($page['children']) 
                ? array_map(fn($child) => $this->buildHierarchyDTO($child), $page['children'])
                : []
        );
    }
}
