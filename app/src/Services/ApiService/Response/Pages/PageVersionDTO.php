<?php

namespace Anymodule\Agentmodule\Services\ApiService\Response\Pages;

use Anymodule\Agentmodule\Services\ApiService\Response\ResponseInterface;

readonly class PageVersionDTO implements ResponseInterface
{
    public function __construct(
        public string $title,
        public string $content,
        public int $pageId,
        public string $versionId,
        public ?string $previousVersionId,
    ) {
    }
}


