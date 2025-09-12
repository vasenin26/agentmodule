<?php

namespace Anymodule\Agentmodule\Entity;

readonly class LLMResult
{
    public function __construct(
        public bool $completed,
        public ?string $answer,
        public array $messages,
        public ?int $prompt_tokens = null,
        public ?int $completion_tokens = null,
        public ?int $total_tokens = null
    )
    {
    }

    public function getTokens(): ?int
    {
        return $this->total_tokens;
    }
}
