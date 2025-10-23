<?php

namespace Anymodule\Agentmodule\Interface;

use Anymodule\Agentmodule\Interface\Git\GitRepoProviderInterface;
use Anymodule\Agentmodule\Interface\Tools\ToolInterface;
use Anymodule\Agentmodule\Interface\Tools\ToolsProviderInterface;

interface ActionsFactoryInterface
{

    public function createSearchRelevantFiles(GitRepoProviderInterface $repoProvider): ActionContract;

    public function createTaskPlanner(ToolInterface $addTasksTool, ToolsProviderInterface $availableTools, GitRepoProviderInterface $repoProvider): ActionContract;
}