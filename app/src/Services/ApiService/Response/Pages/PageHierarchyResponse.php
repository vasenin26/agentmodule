<?php

namespace Anymodule\Agentmodule\Services\ApiService\Response\Pages;

use Anymodule\Agentmodule\Services\ApiService\Response\ResponseInterface;

readonly class PageHierarchyResponse implements ResponseInterface
{
    /**
     * @param PageHierarchyDTO[] $hierarchy
     */
    public function __construct(
        private array $hierarchy
    ) {
    }

    /**
     * @return PageHierarchyDTO[]
     */
    public function getHierarchy(): array
    {
        return $this->hierarchy;
    }
}
