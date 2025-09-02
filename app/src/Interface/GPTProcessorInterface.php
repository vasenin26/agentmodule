<?php

namespace Anymodule\Agentmodule\Interface;

use Anymodule\Agentmodule\Entity\LLMResult;

interface GPTProcessorInterface
{
    public function process(array $messages): LLMResult;
}