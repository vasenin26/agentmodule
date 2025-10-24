<?php

namespace Anymodule\Agentmodule\Interface;

use Anymodule\Agentmodule\Application\Conversation\HandledConversation;
use Vasenin26\Conversation\Interface\Conversation;

interface ConversationFactoryInterface
{
    public function fromMessages(array $messages): Conversation;
    public function handledConversation(array $messages, ProcessHandlerInterface $handler): HandledConversation;
    public function slicedConversation(array $messages): Conversation;
}