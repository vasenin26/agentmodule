<?php

namespace Anymodule\Agentmodule\Utils;

use Anymodule\Agentmodule\Entity\ProcessingResult;
use Vasenin26\Conversation\Interface\Conversation;
use Vasenin26\Conversation\Message;

class HandledConversation implements Conversation
{
    /**
     * @var callable
     */
    private $handler;

    /**
     * @param Conversation $conversation
     * @param callable $handler
     */
    public function __construct(
        private Conversation $conversation,
        callable             $handler,
    )
    {
        $this->handler = $handler;
    }

    public function addMessage(Message $message): void
    {
        $this->conversation->addMessage($message);

        if ($this->handler) {
            call_user_func($this->handler, new ProcessingResult(
                completed: 0,
                answer: null,
                conversation: $this,
                promptTokens: 0,
                completionTokens: 0,
                totalTokens: 0
            ));
        }
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
}