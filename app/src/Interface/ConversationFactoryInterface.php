<?php

namespace Anymodule\Agentmodule\Interface;

use Vasenin26\Conversation\Interface\Conversation;

interface ConversationFactoryInterface
{
    public function fromMessages(array $messages): Conversation;
    public function handledConversation(array $messages, $handler): Conversation;
}