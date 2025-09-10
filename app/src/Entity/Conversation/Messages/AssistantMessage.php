<?php

namespace Anymodule\Agentmodule\Entity\Conversation\Messages;

use Anymodule\Agentmodule\Entity\Conversation\Message;

readonly class AssistantMessage implements Message
{
    public function __construct(
        public string $content,
        public array $toolCallsArray
    )
    {
    }

    public function getContent(): array
    {
        return [
            'content' => $this->content,
            'tool_calls' => $this->toolCallsArray
        ];
    }
}