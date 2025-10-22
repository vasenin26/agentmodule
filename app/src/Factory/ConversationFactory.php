<?php

namespace Anymodule\Agentmodule\Factory;

use Anymodule\Agentmodule\Application\Conversation\ConversationSlice;
use Anymodule\Agentmodule\Application\Conversation\HandledConversation;
use Anymodule\Agentmodule\Interface\ConversationFactoryInterface;
use Vasenin26\Conversation\Factory\ConversationFactory as Vasenin26ConversationFactory;
use Vasenin26\Conversation\Interface\Conversation;

class ConversationFactory implements ConversationFactoryInterface
{
    private Vasenin26ConversationFactory $factory;

    public function __construct()
    {
        $this->factory = new Vasenin26ConversationFactory();
    }

    public function fromMessages(array $messages): Conversation
    {
        return $this->factory->fromMessages($messages);
    }

    public function handledConversation(array $messages, $handler): HandledConversation
    {
        $chat = $this->slicedConversation($messages);
        return new HandledConversation($chat, $handler);
    }

    public function slicedConversation(array $messages): Conversation
    {
        $chat = $this->factory->fromMessages($messages);

        return ConversationSlice::slice($chat);
    }
}