<?php

namespace Anymodule\Agentmodule\Services\Workflows\DTO;

readonly class StepResult
{
    public function __construct(
        public bool $finished,
    )
    {
    }
}