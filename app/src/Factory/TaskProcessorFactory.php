<?php

namespace Anymodule\Agentmodule\Factory;

use Anymodule\Agentmodule\Interface\ConversationFactoryInterface;
use Anymodule\Agentmodule\Interface\LLMFactoryInterface;
use Anymodule\Agentmodule\Interface\Task\TaskProcessor;
use Anymodule\Agentmodule\Interface\Task\TaskProcessorFactoryInterface;
use Anymodule\Agentmodule\Interface\TaskStorageProviderInterface;
use Anymodule\Agentmodule\Interface\Tools\ToolServiceFactoryInterface;
use Anymodule\Agentmodule\Services\TaskProcessor\Actualization;
use Anymodule\Agentmodule\Services\TaskProcessor\CodeProcessor;
use Anymodule\Agentmodule\Services\TaskProcessor\TextProcessor;

class TaskProcessorFactory implements TaskProcessorFactoryInterface
{
    public function __construct(
        private ToolServiceFactoryInterface  $toolsFactory,
        private LLMFactoryInterface          $chatFactory,
        private ConversationFactoryInterface $conversationFactory,
        private TaskStorageProviderInterface $taskStorageProvider,
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