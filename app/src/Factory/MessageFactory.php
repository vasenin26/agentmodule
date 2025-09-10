<?php

namespace Anymodule\Agentmodule\Factory;

use Anymodule\Agentmodule\Entity\Conversation\Message;
use Anymodule\Agentmodule\Entity\Conversation\Messages\AssistantMessage;
use Anymodule\Agentmodule\Entity\Conversation\Messages\SystemMessage;
use Anymodule\Agentmodule\Entity\Conversation\Messages\ToolMessage;
use Anymodule\Agentmodule\Entity\Conversation\Messages\UserMessage;
use Anymodule\Agentmodule\Interface\MessageFactoryInterface;

class MessageFactory implements MessageFactoryInterface
{
    public function createMessage(string $type, array $content): Message
    {
        return match ($type) {
            'user' => UserMessage::createFromData($content),
            'system' => SystemMessage::createFromData($content),
            'assistant' => AssistantMessage::createFromData($content),
            'tool' => ToolMessage::createFromData($content),
            default => throw new \InvalidArgumentException("Unknown message type: {$type}")
        };
    }
    
    public function getSupportedTypes(): array
    {
        return ['user', 'system', 'assistant', 'tool'];
    }
}
