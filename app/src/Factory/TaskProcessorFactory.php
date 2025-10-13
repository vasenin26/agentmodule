<?php

namespace Anymodule\Agentmodule\Factory;

use Anymodule\Agentmodule\Application\TaskProcessor\Actualization;
use Anymodule\Agentmodule\Application\TaskProcessor\CodeProcessor;
use Anymodule\Agentmodule\Application\TaskProcessor\TextProcessor;
use Anymodule\Agentmodule\Interface\ActionsFactoryInterface;
use Anymodule\Agentmodule\Interface\ChatAgentFactoryInterface;
use Anymodule\Agentmodule\Interface\ConversationFactoryInterface;
use Anymodule\Agentmodule\Interface\Task\TaskProcessor;
use Anymodule\Agentmodule\Interface\Task\TaskProcessorFactoryInterface;
use Anymodule\Agentmodule\Interface\TaskStorageProviderInterface;
use Anymodule\Agentmodule\Interface\Tools\ToolServiceFactoryInterface;

class TaskProcessorFactory implements TaskProcessorFactoryInterface
{
    public function __construct(
        private ToolServiceFactoryInterface  $toolsFactory,
        private ChatAgentFactoryInterface    $chatFactory,
        private ConversationFactoryInterface $conversationFactory,
        private TaskStorageProviderInterface $taskStorageProvider,
        private ActionsFactoryInterface      $actionsFactory,
    )
    {
    }

    public function createProcessorForTask(\Anymodule\Agentmodule\Entity\Task $task): TaskProcessor
    {
        if ($task->type == 'code') {
            return new CodeProcessor(
                $this->toolsFactory,
                $this->chatFactory,
                $this->conversationFactory,
                $this->taskStorageProvider,
                $this->actionsFactory,
            );
        }

        if ($task->type == 'actualization') {
            return new Actualization(
                $this->toolsFactory,
                $this->conversationFactory,
                $this->taskStorageProvider,
                $this->chatFactory,
            );
        }

        return new TextProcessor(
            $this->toolsFactory,
            $this->chatFactory,
            $this->conversationFactory,
            $this->taskStorageProvider,
        );
    }
}