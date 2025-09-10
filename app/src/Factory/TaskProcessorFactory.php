<?php

namespace Anymodule\Agentmodule\Factory;

use Anymodule\Agentmodule\Interface\LLMFactoryInterface;
use Anymodule\Agentmodule\Interface\Git\GitTokenProviderInterface;
use Anymodule\Agentmodule\Interface\Task\TaskProcessor;
use Anymodule\Agentmodule\Interface\Task\TaskProcessorFactoryInterface;
use Anymodule\Agentmodule\Interface\Tools\ToolServiceFactoryInterface;

class TaskProcessorFactory implements TaskProcessorFactoryInterface
{
    public function __construct(
        private ToolServiceFactoryInterface $toolsFactory,
        private LLMFactoryInterface         $chatFactory,
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