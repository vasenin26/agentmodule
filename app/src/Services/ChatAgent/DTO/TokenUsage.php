<?php

namespace Anymodule\Agentmodule\Services\ChatAgent\DTO;

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