<?php

namespace Anymodule\Agentmodule\Conversation;

use Anymodule\Agentmodule\Entity\ProcessingResult;
use Anymodule\Agentmodule\Interface\ProcessHandlerInterface;
use Anymodule\Agentmodule\Messages\HandledServiceMessageLink;
use Vasenin26\Conversation\Interface\Conversation;
use Vasenin26\Conversation\Interface\MessageLinkInterface;
use Vasenin26\Conversation\Message;
use Vasenin26\Conversation\Messages\ServiceMessage;

class HandledConversation implements Conversation
{
    public function __construct(
        private Conversation             $conversation,
        private ?ProcessHandlerInterface $handler,
    )
    {
    }

    public function addMessage(Message $message): void
    {
        $this->conversation->addMessage($message);
        $this->fire();
    }

    public function getMessages(): array
    {
        return $this->conversation->getMessages();
    }

    public function getInstructions(): \Generator
    {
        return $this->conversation->getInstructions();
    }

    public function getServices(): \Generator
    {
        return $this->conversation->getServices();
    }

    public function serialize(): array
    {
        return $this->conversation->serialize();
    }

    public function addServiceMessage(ServiceMessage $message): MessageLinkInterface
    {
        $link = $this->conversation->addServiceMessage($message);

        return new HandledServiceMessageLink($link, function () {
            $this->fire();
        });
    }

    private function fire(): void
    {
        if ($this->handler) {
            $this->handler->handle(new ProcessingResult(
                completed: false,
                answer: null,
                conversation: $this,
                contextFill: 0,
                promptTokens: 0,
                completionTokens: 0,
                totalTokens: 0
            ));
        }
    }
}