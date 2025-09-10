<?php

namespace Anymodule\Agentmodule\Interface;

use Anymodule\Agentmodule\Entity\Conversation\Chat;

interface ConversationFactoryInterface
{

    public function fromMessages(array $messages): Chat;
}