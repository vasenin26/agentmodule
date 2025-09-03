<?php

namespace Anymodule\Agentmodule\Factory;

use Anymodule\Agentmodule\Interface\ChatFactoryInterface;
use Anymodule\Agentmodule\Interface\TaskProcessor;
use Anymodule\Agentmodule\Interface\TaskProcessorFactoryInterface;
use Anymodule\Agentmodule\Interface\ToolServiceFactoryInterface;

class TaskProcessorFactory implements TaskProcessorFactoryInterface
{
    public function __construct(
        private ToolServiceFactoryInterface $toolsFactory,
        private ChatFactoryInterface $chatFactory,
    )
    {
    }

    public function createProcessorForTask(\Anymodule\Agentmodule\Entity\Task $task): TaskProcessor
    {
        return new \Anymodule\Agentmodule\Services\TaskProcessor\TaskProcessor(
            $this->toolsFactory,
            $this->chatFactory,
        );
    }
}