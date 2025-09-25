<?php

namespace Anymodule\Agentmodule\Factory;

use Anymodule\Agentmodule\Interface\ConversationFactoryInterface;
use Anymodule\Agentmodule\Utils\HandledConversation;
use Vasenin26\Conversation\Chat;
use Vasenin26\Conversation\Factory\ConversationFactory as Vasenin26ConversationFactory;

class ConversationFactory implements ConversationFactoryInterface
{
    private Vasenin26ConversationFactory $factory;

    public function __construct()
    {
        $this->factory = new Vasenin26ConversationFactory();
    }

    public function fromMessages(array $messages): Chat
    {
        return $this->factory->fromMessages($messages);
    }

    public function handledConversation(array $messages, $handler): HandledConversation
    {
        $chat = $this->factory->fromMessages($messages);
        return new HandledConversation($chat, $handler);
    }
}