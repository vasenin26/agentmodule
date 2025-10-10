<?php

namespace Anymodule\Agentmodule\Entity;

readonly class ModelMeta
{
    public function __construct(
        public string $name,
        public int    $contextSize
    )
    {
    }
}