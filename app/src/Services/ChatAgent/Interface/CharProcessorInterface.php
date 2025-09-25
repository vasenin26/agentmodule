<?php

namespace Anymodule\Agentmodule\Services\ChatAgent\Interface;

use Anymodule\Agentmodule\Interface\Tools\LLMTools;
use Vasenin26\Conversation\Chat;

interface CharProcessorInterface
{
    public function process(Chat $chat, LLMTools $tools): ChatResultInterface;
}