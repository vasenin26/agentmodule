<?php

namespace Anymodule\Agentmodule\Factory;

use Anymodule\Agentmodule\Application\ActionRunner;
use Anymodule\Agentmodule\Application\SubtaskCreator;
use Anymodule\Agentmodule\Interface\ActionRunnerInterface;
use Anymodule\Agentmodule\Interface\AgentMetaProviderInterface;
use Anymodule\Agentmodule\Interface\Factory\ActionRunnerFactoryInterface;
use Anymodule\Agentmodule\Interface\Task\TaskApiInterface;

class ActionRunnerFactory implements ActionRunnerFactoryInterface
{
    public function __construct(
        private TaskApiInterface $taskApi,
        private AgentMetaProviderInterface $agentMetaProvider,
    )
    {
    }

    public function createForTask(\Anymodule\Agentmodule\Entity\Task $task, array $actions): ActionRunnerInterface
    {
        return new ActionRunner(
            $actions,
            new SubtaskCreator($task->id, $this->agentMetaProvider->getAgentUuid(), $this->taskApi),
        );
    }
}