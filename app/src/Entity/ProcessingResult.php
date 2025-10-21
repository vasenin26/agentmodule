<?php

namespace Anymodule\Agentmodule\Entity;

use Vasenin26\Conversation\Interface\Conversation;

readonly class ProcessingResult
{
    public function __construct(
        public bool         $completed,
        public ?string      $answer,
        public Conversation $conversation,
        public ?Context     $context = null,
        public ?string      $modelName,
        public float        $contextFill,
        public ?int         $promptTokens = null,
        public ?int         $completionTokens = null,
        public ?int         $totalTokens = null,
        public ?array       $payload = null,
    )
    {
    }

    public function withAnswer(?string $answer): self
    {
        return new self(
            $this->completed,
            $answer,
            $this->conversation,
            $this->context,
            $this->modelName,
            $this->contextFill,
            $this->promptTokens,
            $this->completionTokens,
            $this->totalTokens,
            $this->payload,
        );
    }
}
