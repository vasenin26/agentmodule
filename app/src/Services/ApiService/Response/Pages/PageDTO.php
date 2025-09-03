<?php

namespace Anymodule\Agentmodule\Services\ApiService\Response\Pages;

use Anymodule\Agentmodule\Services\ApiService\Response\ResponseInterface;

readonly class PageDTO implements ResponseInterface
{
    public function __construct(
        public string $content,
    )
    {
    }
}
