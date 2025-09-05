<?php

namespace Anymodule\Agentmodule\Services\ApiService\Response\Pages;

use Anymodule\Agentmodule\Services\ApiService\Response\ResponseInterface;

readonly class PageChildrenResponse implements ResponseInterface
{
    /**
     * @param PageListDTO[] $children
     */
    public function __construct(
        private array $children
    ) {
    }

    /**
     * @return PageListDTO[]
     */
    public function getChildren(): array
    {
        return $this->children;
    }
}
