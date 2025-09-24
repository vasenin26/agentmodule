<?php

namespace Anymodule\Agentmodule\Factory;

use Anymodule\Agentmodule\Interface\LLMFactoryInterface;
use Anymodule\Agentmodule\Interface\Task\TaskProcessor;
use Anymodule\Agentmodule\Interface\Task\TaskProcessorFactoryInterface;
use Anymodule\Agentmodule\Interface\TaskStorageProviderInterface;
use Anymodule\Agentmodule\Interface\Tools\ToolServiceFactoryInterface;
use Anymodule\Agentmodule\Services\TaskProcessor\CodeProcessor;
use Vasenin26\Conversation\Interface\ConversationFactoryInterface;

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

        return new \Anymodule\Agentmodule\Services\TaskProcessor\TaskProcessor(
            $this->toolsFactory,
            $this->chatFactory,
            $this->conversationFactory,
            $this->taskStorageProvider,
        );
    }
}