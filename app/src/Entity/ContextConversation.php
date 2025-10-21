<?php

namespace Anymodule\Agentmodule\Entity;

use Vasenin26\Conversation\Interface\Conversation;

readonly class ContextConversation
{
    public function __construct(
        public Context      $context,
        public Conversation $conversation,
    )
    {
    }
}