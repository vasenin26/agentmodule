<?php

namespace Anymodule\Agentmodule\Services\ApiService\Response\Pages;

use Anymodule\Agentmodule\Services\ApiService\Response\ResponseInterface;

readonly class PageListResponse implements ResponseInterface
{
    /**
     * @param PageListDTO[] $pages
     */
    public function __construct(
        private array $pages
    ) {
    }

    /**
     * @return PageListDTO[]
     */
    public function getPages(): array
    {
        return $this->pages;
    }
}
