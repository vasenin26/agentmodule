<?php

namespace Anymodule\Agentmodule\Application\ChatAgent\DTO;

final readonly class TokenUsage
{
    public function __construct(
        public int $sent,
        public int $received,
        public int $total
    )
    {
    }
}