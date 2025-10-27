<?php

namespace Anymodule\Agentmodule\Interface\Factory;

use Anymodule\Agentmodule\Application\Tools\Tasks\TaskStorageInterface;
use Anymodule\Agentmodule\Interface\ActionContract;
use Anymodule\Agentmodule\Interface\Git\GitRepoProviderInterface;
use Anymodule\Agentmodule\Interface\Tools\ToolsProviderInterface;

interface ActionsFactoryInterface
{

    public function createSearchRelevantFiles(GitRepoProviderInterface $repoProvider): ActionContract;

    public function createTaskPlanner(TaskStorageInterface $taskStorage, ToolsProviderInterface $availableTools, GitRepoProviderInterface $repoProvider): ActionContract;
}