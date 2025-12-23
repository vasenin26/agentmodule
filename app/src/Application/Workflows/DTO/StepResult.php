<?php

namespace Anymodule\Agentmodule\Application\Workflows\DTO;

readonly class StepResult
{
    public function __construct(
        public string $success,
    )
    {
    }
}