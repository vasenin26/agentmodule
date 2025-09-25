<?php

namespace Anymodule\Agentmodule\Entity;

use Vasenin26\Conversation\Chat;

readonly class ProcessingResult
{
    public function __construct(
        public bool    $completed,
        public ?string $answer,
        public Chat    $messages,
        public ?int    $promptTokens = null,
        public ?int    $completionTokens = null,
        public ?int    $totalTokens = null
    )
    {
    }
}
