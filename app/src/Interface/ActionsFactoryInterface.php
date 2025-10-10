<?php

namespace Anymodule\Agentmodule\Interface;

use Anymodule\Agentmodule\Interface\Tools\ToolInterface;
use Anymodule\Agentmodule\Interface\Tools\ToolsProviderInterface;

interface ActionsFactoryInterface
{

    public function createSearchRelevantFiles(): ActionContract;

    public function createTaskPlanner(ToolInterface $addTasksTool, ToolsProviderInterface $availableTools): ActionContract;
}