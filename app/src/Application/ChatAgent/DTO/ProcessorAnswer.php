<?php

namespace Anymodule\Agentmodule\Application\ChatAgent\DTO;

final readonly class ProcessorAnswer
{
    public function __construct(
        public string $message,
    )
    {
    }
}