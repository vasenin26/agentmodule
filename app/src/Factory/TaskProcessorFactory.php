<?php

namespace Anymodule\Agentmodule\Factory;

use Anymodule\Agentmodule\Application\TaskProcessor\Actualization;
use Anymodule\Agentmodule\Application\TaskProcessor\CodeProcessor;
use Anymodule\Agentmodule\Application\TaskProcessor\TaskGenerationProcessor;
use Anymodule\Agentmodule\Application\TaskProcessor\TechPlaneGeneration;
use Anymodule\Agentmodule\Application\TaskProcessor\TextProcessor;
use Anymodule\Agentmodule\Interface\Factory\ActionRunnerFactoryInterface;
use Anymodule\Agentmodule\Interface\Factory\ActionsFactoryInterface;
use Anymodule\Agentmodule\Interface\Factory\ChatAgentFactoryInterface;
use Anymodule\Agentmodule\Interface\Factory\ConversationFactoryInterface;
use Anymodule\Agentmodule\Interface\Storage\TaskStorageProviderInterface;
use Anymodule\Agentmodule\Interface\Task\TaskProcessor;
use Anymodule\Agentmodule\Interface\Task\TaskProcessorFactoryInterface;
use Anymodule\Agentmodule\Interface\Tools\ToolServiceFactoryInterface;

class TaskProcessorFactory implements TaskProcessorFactoryInterface
{
    private array $processors = [];

    public function __construct(
        private ToolServiceFactoryInterface  $toolsFactory,
        private ChatAgentFactoryInterface    $chatFactory,
        private ConversationFactoryInterface $conversationFactory,
        private TaskStorageProviderInterface $taskStorageProvider,
        private ActionRunnerFactoryInterface $actionRunnerFactory,
        private ActionsFactoryInterface      $actionsFactory,
    )
    {
        $this->processors = [
            $this->createCodeProcessor(),
            $this->createActualizationProcessor(),
            $this->createTechplaneProcessor(),
            $this->createTaskDescriptionProcessor(),
            $this->createTextProcessor()
        ];
    }

    public function createProcessorForTask(\Anymodule\Agentmodule\Entity\Task $task): TaskProcessor
    {
        foreach ($this->processors as $processor) {
            if ($processor->supports($task)) {
                return $processor;
            }
        }

        throw new \Exception('Task not supported');
    }

    private function createCodeProcessor(): TaskProcessor
    {
        return new CodeProcessor(
            $this->toolsFactory,
            $this->chatFactory,
            $this->conversationFactory,
            $this->taskStorageProvider,
            $this->actionRunnerFactory,
            $this->actionsFactory,
        );
    }

    private function createActualizationProcessor(): TaskProcessor
    {
        return new Actualization(
            $this->toolsFactory,
            $this->conversationFactory,
            $this->taskStorageProvider,
            $this->chatFactory,
            $this->actionRunnerFactory,
            $this->actionsFactory,
        );
    }

    private function createTechplaneProcessor(): TaskProcessor
    {
        return new TechPlaneGeneration(
            $this->toolsFactory,
            $this->conversationFactory,
            $this->chatFactory,
            $this->actionRunnerFactory,
            $this->actionsFactory,
        );
    }

    private function createTextProcessor(): TaskProcessor
    {
        return new TextProcessor(
            $this->toolsFactory,
            $this->chatFactory,
            $this->conversationFactory,
            $this->taskStorageProvider,
            $this->actionRunnerFactory,
            $this->actionsFactory,
        );
    }

    private function createTaskDescriptionProcessor(): TaskProcessor
    {
        return new TaskGenerationProcessor(
            $this->toolsFactory,
            $this->conversationFactory,
            $this->chatFactory,
            $this->actionRunnerFactory,
            $this->actionsFactory,
        );
    }
}