<?php

namespace Anymodule\Agentmodule\Conversation;

use Vasenin26\Conversation\Chat;
use Vasenin26\Conversation\Interface\Conversation;
use Vasenin26\Conversation\Interface\MessageLinkInterface;
use Vasenin26\Conversation\Message;
use Vasenin26\Conversation\Messages\ServiceMessage;

class ConversationSlice implements Conversation
{
    private Conversation $slice;

    public function __construct(
        private Conversation $conversation,
    )
    {
        $this->slice = new Chat();
    }

    public function addMessage(Message $message): void
    {
        $this->slice->addMessage($message);
        $this->conversation->addMessage($message);
    }

    public function addServiceMessage(ServiceMessage $message): MessageLinkInterface
    {
        $this->slice->addMessage($message);

        return $this->conversation->addServiceMessage($message);
    }

    public function getMessages(): array
    {
        return $this->slice->getMessages();
    }

    public function getInstructions(): \Generator
    {
        return $this->slice->getInstructions();
    }

    public function getServices(): \Generator
    {
        return $this->slice->getServices();
    }

    public function serialize(): array
    {
        return $this->conversation->serialize();
    }
}