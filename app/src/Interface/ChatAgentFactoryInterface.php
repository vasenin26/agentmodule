<?php

namespace Anymodule\Agentmodule\Interface;


use Anymodule\Agentmodule\Interface\Tools\ToolsProviderInterface;

interface ChatAgentFactoryInterface
{
    public function createAgent(ToolsProviderInterface $tools): ActionContract;

    public function createContextAgent(ToolsProviderInterface $tools): ContextActionContract;
}