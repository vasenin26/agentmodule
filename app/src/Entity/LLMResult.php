<?php

namespace Anymodule\Agentmodule\Entity;

readonly class LLMResult
{
    public function __construct(
        public string $answer,
        public array $messages,
        public ?int $prompt_tokens = null,
        public ?int $completion_tokens = null,
        public ?int $total_tokens = null
    )
    {
    }
    
    /**
     * Legacy метод для обратной совместимости
     * @deprecated Используйте total_tokens напрямую
     */
    public function getTokens(): ?int
    {
        return $this->total_tokens;
    }
}
