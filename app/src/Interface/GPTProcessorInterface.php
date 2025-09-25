<?php

namespace Anymodule\Agentmodule\Interface;

use Anymodule\Agentmodule\Entity\ProcessingResult;
use Vasenin26\Conversation\Interface\Conversation;

interface GPTProcessorInterface
{
    public function process(Conversation $chat, $processHandler, bool $resultRequired = false): ProcessingResult;
}