<?php

namespace Anymodule\Agentmodule\Services\ApiService\Response\Pages;

use Anymodule\Agentmodule\Services\ApiService\Response\ResponseInterface;

readonly class PageDTO implements ResponseInterface
{
    public function __construct(
        public int $id,
        public string $title,
        public string $content,
        public array $files = [],
    )
    {
    }
}
