<?php

namespace Anymodule\Agentmodule\Interface;

use Anymodule\Agentmodule\Interface\Tools\ToolInterface;

interface ActionsFactoryInterface
{

    public function createSearchRelevantFiles(): ActionContract;

    public function createTaskPlanner(ToolInterface $addTasksTool): ActionContract;
}