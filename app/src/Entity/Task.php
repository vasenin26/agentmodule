<?php

namespace Anymodule\Agentmodule\Entity;

readonly class Task
{
    public function __construct(
        public int $id,
        public array $messages,
    )
    {
    }
}