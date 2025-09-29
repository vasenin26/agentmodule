<?php

namespace Anymodule\Agentmodule\Interface;

use Anymodule\Agentmodule\Interface\Tools\ToolInterface;
use Anymodule\Agentmodule\Interface\Tools\ToolsProvider;

interface ActionsFactoryInterface
{

    public function createSearchRelevantFiles(): ActionContract;

    public function createTaskPlanner(ToolInterface $addTasksTool, ToolsProvider $availableTools): ActionContract;
}