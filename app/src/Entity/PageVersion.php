<?php

namespace Anymodule\Agentmodule\Entity;

readonly class PageVersion
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


