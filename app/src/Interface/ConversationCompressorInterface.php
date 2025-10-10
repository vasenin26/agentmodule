<?php

namespace Anymodule\Agentmodule\Interface;

use Vasenin26\Conversation\Interface\Conversation;

interface ConversationCompressorInterface
{
    public function compress(Conversation $conversation): Conversation;
}