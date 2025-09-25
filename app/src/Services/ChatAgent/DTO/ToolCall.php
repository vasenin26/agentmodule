<?php

namespace Anymodule\Agentmodule\Services\ChatAgent\DTO;

final readonly class ToolCall
{
    public function __construct(
        public string $id,
        public string $name,
        public string $arguments,
    )
    {
    }
}