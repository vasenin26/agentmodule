<?php

namespace Anymodule\Agentmodule\Factory;

use Anymodule\Agentmodule\Actions\SearchRelevantFiles;
use Anymodule\Agentmodule\Actions\TaskPlanner;
use Anymodule\Agentmodule\Interface\ActionContract;
use Anymodule\Agentmodule\Interface\ActionsFactoryInterface;
use Anymodule\Agentmodule\Interface\ChatAgentFactoryInterface;
use Anymodule\Agentmodule\Interface\Tools\ToolInterface;
use Anymodule\Agentmodule\Interface\Tools\ToolServiceFactoryInterface;
use Anymodule\Agentmodule\Interface\Tools\ToolsProvider;
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

    public function createSearchRelevantFiles(): ActionContract
    {
        return new SearchRelevantFiles(
            $this->chatFactory,
            $this->toolsFactory,
            new ActionInformation(),
        );
    }

    public function createTaskPlanner(ToolInterface $addTasksTool, ToolsProvider $availableTools): ActionContract
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
            $addTasksTool,
            $availableToolsDescription,
            new ActionInformation(),
        );
    }
}