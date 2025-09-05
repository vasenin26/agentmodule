<?php

namespace Anymodule\Agentmodule\Entity;

readonly class Page
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