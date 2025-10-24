<?php

namespace Anymodule\Agentmodule\Factory;

use Anymodule\Agentmodule\Application\Actions\SearchRelevantFiles;
use Anymodule\Agentmodule\Application\Actions\TaskPlanner;
use Anymodule\Agentmodule\Application\Tools\Tasks\TaskStorageInterface;
use Anymodule\Agentmodule\Interface\ActionContract;
use Anymodule\Agentmodule\Interface\ActionsFactoryInterface;
use Anymodule\Agentmodule\Interface\ChatAgentFactoryInterface;
use Anymodule\Agentmodule\Interface\Git\GitRepoProviderInterface;
use Anymodule\Agentmodule\Interface\Tools\ToolInterface;
use Anymodule\Agentmodule\Interface\Tools\ToolServiceFactoryInterface;
use Anymodule\Agentmodule\Interface\Tools\ToolsProviderInterface;
use Anymodule\Agentmodule\Utils\Log;
use Anymodule\Agentmodule\Utils\Mapper\ActionInformation;

final readonly class ActionsFactory implements ActionsFactoryInterface
{

    public function __construct(
        private ToolServiceFactoryInterface $toolsFactory,
        private ChatAgentFactoryInterface   $chatFactory,
    )
    {
    }

    public function createSearchRelevantFiles(GitRepoProviderInterface $repoProvider): ActionContract
    {
        return new SearchRelevantFiles(
            $this->chatFactory,
            $this->toolsFactory,
            new ActionInformation(),
            $repoProvider,
        );
    }

    public function createTaskPlanner(TaskStorageInterface $taskStorage, ToolsProviderInterface $availableTools, GitRepoProviderInterface $repoProvider): ActionContract
    {
        $availableToolsDescription = [];

        foreach ($availableTools->getMeta() as $toolMeta) {
            $f = $toolMeta['function'] ?? null;
            if (is_null($f) || empty($f['name']) || empty($f['description'])) {
                Log::warning('Unknown tool meta format', $toolMeta);
                continue;
            }

            $availableToolsDescription[$f['name']] = $f['description'];
        }

        return new TaskPlanner(
            $this->chatFactory,
            $this->toolsFactory,
            $taskStorage,
            $availableToolsDescription,
            new ActionInformation(),
            $repoProvider,
        );
    }
}