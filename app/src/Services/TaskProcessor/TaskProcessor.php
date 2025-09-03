<?php

namespace Anymodule\Agentmodule\Services\TaskProcessor;

use Anymodule\Agentmodule\Entity\LLMResult;
use Anymodule\Agentmodule\Entity\Task;
use Anymodule\Agentmodule\Interface\ChatFactoryInterface;
use Anymodule\Agentmodule\Interface\TaskApi;
use Anymodule\Agentmodule\Interface\ToolServiceFactoryInterface;

class TaskProcessor implements \Anymodule\Agentmodule\Interface\TaskProcessor
{
    public function __construct(
        private ToolServiceFactoryInterface $toolsFactory,
        private ChatFactoryInterface $chatFactory
    )
    {
    }

    public function process(Task $task): LLMResult
    {
        $tools = $this->toolsFactory->withMainTools();
        $chat = $this->chatFactory->createChat($tools);
        return $chat->process($task->messages);
    }
}