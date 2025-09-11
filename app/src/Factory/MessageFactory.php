<?php

namespace Anymodule\Agentmodule\Factory;

use Vasenin26\Conversation\Message;
use Vasenin26\Conversation\Messages\AssistantMessage;
use Vasenin26\Conversation\Messages\SystemMessage;
use Vasenin26\Conversation\Messages\ToolMessage;
use Vasenin26\Conversation\Messages\UserMessage;
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
