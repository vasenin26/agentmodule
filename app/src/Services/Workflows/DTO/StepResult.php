<?php

namespace Anymodule\Agentmodule\Services\Workflows\DTO;

readonly class StepResult
{
    public function __construct(
        public bool    $finished,
        public ?int    $promptTokens = null,
        public ?int    $completionTokens = null,
        public ?int    $totalTokens = null,
        public ?string $modelName = null,
        public ?float  $contextFill = null,
    ) {}
}