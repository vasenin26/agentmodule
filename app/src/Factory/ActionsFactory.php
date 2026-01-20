<?php

namespace Anymodule\Agentmodule\Factory;

use Anymodule\Agentmodule\Application\Actions\SearchRelevantFiles;
use Anymodule\Agentmodule\Application\Actions\TaskPlanner;
use Anymodule\Agentmodule\Application\Enum\TaskTypes;
use Anymodule\Agentmodule\Application\Logger\Log;
use Anymodule\Agentmodule\Application\Support\Mapper\ActionInformation;
use Anymodule\Agentmodule\Application\Tools\Tasks\TaskStorageInterface;
use Anymodule\Agentmodule\Interface\ActionContract;
use Anymodule\Agentmodule\Interface\AgentMetaProviderInterface;
use Anymodule\Agentmodule\Interface\Factory\ActionsFactoryInterface;
use Anymodule\Agentmodule\Interface\Factory\ChatAgentFactoryInterface;
use Anymodule\Agentmodule\Interface\Git\GitRepoProviderInterface;
use Anymodule\Agentmodule\Interface\ProjectSettingsProviderInterface;
use Anymodule\Agentmodule\Interface\Tools\ToolServiceFactoryInterface;
use Anymodule\Agentmodule\Interface\Tools\ToolsProviderInterface;

final readonly class ActionsFactory implements ActionsFactoryInterface
{

    public function __construct(
        private AgentMetaProviderInterface       $agentMeta,
        private ProjectSettingsProviderInterface $projectSettingsProvider,
        private ToolServiceFactoryInterface      $toolsFactory,
        private ChatAgentFactoryInterface        $chatFactory,
    )
    {
    }

    public function createSearchRelevantFiles(
        int                      $projectId,
        GitRepoProviderInterface $repoProvider
    ): ActionContract
    {
        return new SearchRelevantFiles(
            $this->defineTypeModel($projectId, TaskTypes::SearchRelevantFiles->value),
            $this->chatFactory,
            $this->toolsFactory,
            new ActionInformation(),
            $repoProvider,
        );
    }

    public function createTaskPlanner(
        int                      $projectId,
        TaskStorageInterface     $taskStorage,
        ToolsProviderInterface   $availableTools,
        GitRepoProviderInterface $repoProvider
    ): ActionContract
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
            $this->defineTypeModel($projectId, TaskTypes::TaskPlaning->value),
            $this->chatFactory,
            $this->toolsFactory,
            $taskStorage,
            $availableToolsDescription,
            new ActionInformation(),
            $repoProvider,
        );
    }

    private function defineTypeModel(int $projectId, string $type): string
    {
        $projectSettings = $this->projectSettingsProvider->getProjectSetting($projectId);
        $modelName = $projectSettings->getPreferredModel($type);

        if (!$modelName) {
            $modelName = $this->agentMeta->getDefaultModel();
        }

        return $modelName;
    }
}