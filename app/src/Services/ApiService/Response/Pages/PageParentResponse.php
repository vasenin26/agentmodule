<?php

namespace Anymodule\Agentmodule\Services\ApiService\Response\Pages;

use Anymodule\Agentmodule\Services\ApiService\Response\ResponseInterface;

readonly class PageParentResponse implements ResponseInterface
{
    public function __construct(
        private ?PageDTO $page
    ) {
    }

    public function getPage(): ?PageDTO
    {
        return $this->page;
    }
}
