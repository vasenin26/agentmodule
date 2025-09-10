<?php

namespace Anymodule\Agentmodule\Entity\Conversation;

class Chat
{
    public function __construct(
        private array $messages = []
    )
    {
    }

    public function addMessage(Message $message): void
    {
        $this->messages[] = $message;
    }

    public function getMessages(): array
    {
        return $this->messages;
    }

    public function serialize(): array
    {
        $messages = [];

        foreach ($this->messages as $message) {
            $messages[] = [
                'type' => $message->getType(),
                'message' => $message->getContent(),
            ];
        }

        return $messages;
    }
}