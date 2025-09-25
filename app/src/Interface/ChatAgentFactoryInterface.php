<?php

namespace Anymodule\Agentmodule\Interface;


use Anymodule\Agentmodule\Interface\Tools\LLMTools;

interface ChatAgentFactoryInterface
{
    public function createAgent(LLMTools $tools): ActionContract;
}