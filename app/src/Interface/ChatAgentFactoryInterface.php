<?php

namespace Anymodule\Agentmodule\Interface;


use Anymodule\Agentmodule\Interface\Tools\ToolsProvider;

interface ChatAgentFactoryInterface
{
    public function createAgent(ToolsProvider $tools): ActionContract;
}