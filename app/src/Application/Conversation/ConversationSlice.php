<?php

namespace Anymodule\Agentmodule\Application\Conversation;

use Anymodule\Agentmodule\Utils\Log;
use Vasenin26\Conversation\Chat;
use Vasenin26\Conversation\Interface\Conversation;
use Vasenin26\Conversation\Interface\MessageLinkInterface;
use Vasenin26\Conversation\Message;
use Vasenin26\Conversation\Messages\ServiceMessage;
use Vasenin26\Conversation\Messages\SliceMessage;
use Vasenin26\Conversation\Messages\SystemMessage;
use Vasenin26\Conversation\Messages\UserTaskMessage;

class ConversationSlice implements Conversation
{
    private Conversation $slice;

    public function __construct(
        private Conversation $conversation,
    )
    {
        $this->slice = new Chat();
    }

    public static function slice(Conversation $chat): ConversationSlice
    {
        Log::info("Create conversation slice");

        $slice = new self($chat);
        $lastSlicePosition = 0;

        foreach ($chat->getMessages() as $idx => $message) {
            if ($message instanceof SliceMessage) {
                $lastSlicePosition = $idx;
            }
        }

        Log::info("Conversation last slice index: {$lastSlicePosition}");

        foreach ($chat->getMessages() as $message) {
            if ($message instanceof UserTaskMessage || $message instanceof SystemMessage) {
                $slice->push($message);
            }
        }

        foreach (array_slice(iterator_to_array($chat->getMessages()), $lastSlicePosition) as $message) {
            $slice->push($message);
        }

        return $slice;
    }

    public function push(Message $message): void
    {
        $this->slice->addMessage($message);
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