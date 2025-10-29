<?php

namespace Anymodule\Agentmodule\Application\ChatAgent;

use Anymodule\Agentmodule\Entity\Context;
use Anymodule\Agentmodule\Entity\ContextConversation;
use Anymodule\Agentmodule\Interface\ActionContract;
use Anymodule\Agentmodule\Interface\ContextActionContract;
use Vasenin26\Conversation\Interface\Conversation;

class ChatAgent implements ActionContract
{

    public function __construct(
        private ContextActionContract $contextAgent,
    )
    {
    }

    public function execute(Conversation $conversation): \Generator
    {
        return $this->contextAgent->execute(new ContextConversation(Context::empty(), $conversation));
    }
}