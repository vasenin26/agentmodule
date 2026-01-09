<?php

namespace Anymodule\Agentmodule\Application\Workflow\Nodes;

use Anymodule\Agentmodule\Application\Workflow\Interface\NodeProcessorInterface;
use Anymodule\Agentmodule\Application\Workflow\Interface\PlanableContextInterface;
use Anymodule\Agentmodule\Interface\Factory\ChatAgentFactoryInterface;
use Anymodule\Agentmodule\Interface\Git\GitRepoProviderInterface;
use Anymodule\Agentmodule\Interface\Tools\ToolServiceFactoryInterface;
use Anymodule\Agentmodule\Services\TaskStorageProvider;
use Anymodule\Agentmodule\Services\Workflows\Interface\Context;

class Developer implements NodeProcessorInterface
{
    public function __construct(
        private ChatAgentFactoryInterface $chatAgentFactory,
        private GitRepoProviderInterface  $gitRepoProvider,
        private ToolServiceFactoryInterface  $toolsFactory,
        private TaskStorageProvider          $taskStorageProvider,
    )
    {
    }

    public function process(Context $ctx): \Generator
    {
        $toolsBuilder = $this->toolsFactory->createToolsBuilderWithRepository($this->gitRepoProvider)
            ->withGit($this->gitRepoProvider)
            ->withRepoManagement($this->gitRepoProvider)
            ->withEditor($this->gitRepoProvider)
            ->withTasks( $this->taskStorageProvider->getTaskStorage($ctx->getTask()->id))
            ->withTerminal();

        return $this->chatAgentFactory
            ->createContextAgent($toolsBuilder->build(), $this->gitRepoProvider)
            ->execute($ctx->getContextConversation());
    }
}