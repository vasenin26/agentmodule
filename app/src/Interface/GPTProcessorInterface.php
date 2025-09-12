<?php

namespace Anymodule\Agentmodule\Interface;

use Vasenin26\Conversation\Chat;
use Anymodule\Agentmodule\Entity\LLMResult;

interface GPTProcessorInterface
{
    public function process(Chat $chat, $processHandler, bool $resultRequired = false): LLMResult;
}