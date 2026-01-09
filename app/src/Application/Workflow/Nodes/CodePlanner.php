<?php

namespace Anymodule\Agentmodule\Application\Workflow\Nodes;

use Anymodule\Agentmodule\Application\Workflow\Interface\NodeProcessorInterface;
use Anymodule\Agentmodule\Application\Workflow\Interface\PlanableContextInterface;
use Anymodule\Agentmodule\Factory\ToolServiceFactory;
use Anymodule\Agentmodule\Interface\Factory\ActionRunnerFactoryInterface;
use Anymodule\Agentmodule\Interface\Factory\ActionsFactoryInterface;
use Anymodule\Agentmodule\Interface\Git\GitRepoProviderInterface;
use Anymodule\Agentmodule\Services\TaskStorageProvider;
use Anymodule\Agentmodule\Services\Workflows\DTO\StepResult;
use Anymodule\Agentmodule\Services\Workflows\Interface\Context;

class CodePlanner implements NodeProcessorInterface
{

    public function __construct(
        private ActionRunnerFactoryInterface $runnerFactory,
        private ActionsFactoryInterface      $af,
        private TaskStorageProvider          $taskStorageProvider,
        private ToolServiceFactory           $toolServiceFactory,
        private GitRepoProviderInterface     $gitRepoProvider,
    )
    {
    }

    public function process(Context $ctx): \Generator
    {
        if ($ctx instanceof PlanableContextInterface) {
            $task = $ctx->getTask();
            $action = $this->af->createTaskPlanner(
                $ctx->getProjectId(),
                $this->taskStorageProvider->getTaskStorage($task->id),
                $this->toolServiceFactory->withTools($ctx->getAvailableTools()),
                $this->gitRepoProvider
            );

            $this->runnerFactory->createForTask($task, ['plane' => $action])
                ->run($ctx->getContextConversation()->conversation);

            $ctx->setPlane([]);//intermediate deprecated solution pass the task plan with conversation
        }

        yield new StepResult(true);
    }
}