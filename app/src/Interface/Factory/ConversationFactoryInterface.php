<?php

namespace Anymodule\Agentmodule\Interface\Factory;

use Anymodule\Agentmodule\Application\Conversation\HandledConversation;
use Anymodule\Agentmodule\Interface\ProcessHandlerInterface;
use Vasenin26\Conversation\Interface\Conversation;

interface ConversationFactoryInterface
{
    public function fromMessages(array $messages): Conversation;
    public function handledConversation(array $messages, ProcessHandlerInterface $handler): HandledConversation;
    public function slicedConversation(array $messages): Conversation;
}