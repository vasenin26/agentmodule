<?php

namespace Anymodule\Agentmodule\Entity;

readonly class TaskState
{
    public function __construct(
        public string $status
    )
    {
    }
}