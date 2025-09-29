<?php

namespace Anymodule\Agentmodule\Entity;

use Vasenin26\Conversation\Interface\Conversation;

readonly class ProcessingResult
{
    public function __construct(
        public bool         $completed,
        public ?string      $answer,
        public Conversation $conversation,
        public ?int         $promptTokens = null,
        public ?int         $completionTokens = null,
        public ?int         $totalTokens = null,
        public ?array       $payload = null,
    )
    {
    }
}
