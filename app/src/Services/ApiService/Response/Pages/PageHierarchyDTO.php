<?php

namespace Anymodule\Agentmodule\Services\ApiService\Response\Pages;

use Anymodule\Agentmodule\Services\ApiService\Response\ResponseInterface;

readonly class PageHierarchyDTO implements ResponseInterface
{
    public function __construct(
        public int $id,
        public string $title,
        public array $children = [],
    )
    {
    }
}
