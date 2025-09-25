<?php

namespace Anymodule\Agentmodule\Interface;

use Anymodule\Agentmodule\Services\ChatAgent\ChatAgent;
use Vasenin26\Conversation\Chat;

interface ChatAgentFactoryInterface
{
    public function createAgent(array $tools): ChatAgent;
}