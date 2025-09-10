<?php

namespace Anymodule\Agentmodule\Interface;

use Anymodule\Agentmodule\Entity\Conversation\Chat;
use Anymodule\Agentmodule\Entity\LLMResult;

interface GPTProcessorInterface
{
    public function process(Chat $messages): LLMResult;
}