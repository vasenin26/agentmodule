<?php

namespace Anymodule\Agentmodule\Services\ChatAgent\DTO;

final readonly class ProcessorAnswer
{
    public function __construct(
        public string $message,
    )
    {
    }
}