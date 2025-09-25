<?php

namespace Anymodule\Agentmodule\Interface;

use Vasenin26\Conversation\Chat;
use Anymodule\Agentmodule\Entity\ProcessingResult;

interface ChatAgentInterface
{
    public function process(Chat $chat, $processHandler): ProcessingResult;
}