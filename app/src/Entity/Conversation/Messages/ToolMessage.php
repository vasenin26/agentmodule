<?php

namespace Anymodule\Agentmodule\Entity\Conversation\Messages;

use Anymodule\Agentmodule\Entity\Conversation\Message;

class AssistantMessage implements Message
{
    public function __construct(
        private string $content,
        private array $toolCallsArray
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