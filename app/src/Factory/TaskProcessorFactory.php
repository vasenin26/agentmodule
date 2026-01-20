<?php

namespace Anymodule\Agentmodule\Factory;

use Anymodule\Agentmodule\Application\TaskProcessor\{
    CodeProcessor,
    Actualization,
    TechPlaneGeneration,
    TaskGenerationProcessor,
    TerminalProcessor,
    TextProcessor
};
use Anymodule\Agentmodule\Interface\Factory\ActionRunnerFactoryInterface;
use Anymodule\Agentmodule\Interface\Factory\ActionsFactoryInterface;
use Anymodule\Agentmodule\Interface\Factory\ChatAgentFactoryInterface;
use Anymodule\Agentmodule\Interface\Factory\ConversationFactoryInterface;
use Anymodule\Agentmodule\Interface\Storage\TaskStorageProviderInterface;
use Anymodule\Agentmodule\Interface\Task\TaskProcessor;
use Anymodule\Agentmodule\Interface\Tools\ToolServiceFactoryInterface;

final class TaskProcessorFactory
{
    public function __construct(
        private ToolServiceFactoryInterface  $toolsFactory,
        private ChatAgentFactoryInterface    $chatFactory,
        private ConversationFactoryInterface $conversationFactory,
        private TaskStorageProviderInterface $taskStorageProvider,
        private ActionRunnerFactoryInterface $actionRunnerFactory,
        private ActionsFactoryInterface      $actionsFactory,
    ) {}

    public function create(string $processorClass): TaskProcessor
    {
        return match ($processorClass) {

            CodeProcessor::class => new CodeProcessor(
                $this->toolsFactory,
                $this->chatFactory,
                $this->conversationFactory,
                $this->taskStorageProvider,
                $this->actionRunnerFactory,
                $this->actionsFactory,
            ),

            Actualization::class => new Actualization(
                $this->toolsFactory,
                $this->conversationFactory,
                $this->taskStorageProvider,
                $this->chatFactory,
                $this->actionRunnerFactory,
                $this->actionsFactory,
            ),

            TechPlaneGeneration::class => new TechPlaneGeneration(
                $this->toolsFactory,
                $this->conversationFactory,
                $this->chatFactory,
                $this->actionRunnerFactory,
                $this->actionsFactory,
            ),

            TaskGenerationProcessor::class => new TaskGenerationProcessor(
                $this->toolsFactory,
                $this->conversationFactory,
                $this->chatFactory,
                $this->actionRunnerFactory,
                $this->actionsFactory,
            ),

            TerminalProcessor::class => new TerminalProcessor(
                $this->toolsFactory,
                $this->conversationFactory,
            ),

            TextProcessor::class => new TextProcessor(
                $this->toolsFactory,
                $this->chatFactory,
                $this->conversationFactory,
                $this->taskStorageProvider,
                $this->actionRunnerFactory,
                $this->actionsFactory,
            ),

            default => throw new \LogicException("Unknown processor: $processorClass"),
        };
    }
}