<?php

namespace Anymodule\Agentmodule\Application\Conversation;

use Anymodule\Agentmodule\Application\Logger\Log;
use Anymodule\Agentmodule\Application\Messages\HandledServiceMessageLink;
use Anymodule\Agentmodule\Entity\ProcessingResult;
use Anymodule\Agentmodule\Interface\ProcessHandlerInterface;
use Vasenin26\Conversation\Interface\Conversation;
use Vasenin26\Conversation\Interface\MessageLinkInterface;
use Vasenin26\Conversation\Message;
use Vasenin26\Conversation\Messages\ServiceMessage;

class HandledConversation implements Conversation
{
    public function __construct(
        public readonly Conversation     $conversation,
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
        Log::info("📩 Message fired");
        
        if ($this->handler) {
            $this->handler->handle(new ProcessingResult(
                completed: false,
                answer: null,
                conversation: $this,
                context: null,
                modelName: null,
                contextFill: 0,
                promptTokens: 0,
                completionTokens: 0,
                totalTokens: 0
            ));
        }
    }

    public function hasNoUserAnswer(): bool
    {
        return $this->conversation->hasNoUserAnswer();
    }
}