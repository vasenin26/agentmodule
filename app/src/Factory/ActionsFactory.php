<?php

namespace Anymodule\Agentmodule\Factory;

use Anymodule\Agentmodule\Actions\SearchRelevantFiles;
use Anymodule\Agentmodule\Actions\TaskPlanner;
use Anymodule\Agentmodule\Interface\ActionContract;
use Anymodule\Agentmodule\Interface\ActionsFactoryInterface;
use Anymodule\Agentmodule\Interface\ChatAgentFactoryInterface;
use Anymodule\Agentmodule\Interface\Tools\ToolInterface;
use Anymodule\Agentmodule\Interface\Tools\ToolServiceFactoryInterface;
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

    public function createTaskPlanner(ToolInterface $addTasksTool): ActionContract
    {
        return new TaskPlanner(
            $this->chatFactory,
            $this->toolsFactory,
            $addTasksTool,
            new ActionInformation(),
        );
    }
}