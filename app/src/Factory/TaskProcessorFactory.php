<?php

namespace Anymodule\Agentmodule\Factory;

use Anymodule\Agentmodule\Interface\ActionsFactoryInterface;
use Anymodule\Agentmodule\Interface\ConversationFactoryInterface;
use Anymodule\Agentmodule\Interface\LLMFactoryInterface;
use Anymodule\Agentmodule\Interface\Task\TaskProcessor;
use Anymodule\Agentmodule\Interface\Task\TaskProcessorFactoryInterface;
use Anymodule\Agentmodule\Interface\TaskStorageProviderInterface;
use Anymodule\Agentmodule\Interface\Tools\ToolServiceFactoryInterface;
use Anymodule\Agentmodule\TaskProcessor\Actualization;
use Anymodule\Agentmodule\TaskProcessor\CodeProcessor;
use Anymodule\Agentmodule\TaskProcessor\TextProcessor;

class TaskProcessorFactory implements TaskProcessorFactoryInterface
{
    public function __construct(
        private ToolServiceFactoryInterface  $toolsFactory,
        private LLMFactoryInterface          $chatFactory,
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