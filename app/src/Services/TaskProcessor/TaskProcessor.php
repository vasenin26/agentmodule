<?php

namespace Anymodule\Agentmodule\Services\TaskProcessor;

use Anymodule\Agentmodule\Entity\LLMResult;
use Anymodule\Agentmodule\Entity\Task;
use Anymodule\Agentmodule\Interface\ChatFactoryInterface;
use Anymodule\Agentmodule\Interface\Tools\ToolServiceFactoryInterface;

class TaskProcessor implements \Anymodule\Agentmodule\Interface\Task\TaskProcessor
{
    public function __construct(
        private ToolServiceFactoryInterface $toolsFactory,
        private ChatFactoryInterface $chatFactory
    )
    {
    }

    public function process(Task $task): LLMResult
    {
        $toolsBuilder = $this->toolsFactory->createToolsBuilder();

        if($task->projectId) {
            $toolsBuilder->withProject($task->projectId);
            $toolsBuilder->withGit();
        }

        $tools = $toolsBuilder->build();
        $chat = $this->chatFactory->createChat($tools);
        return $chat->process($task->messages);
    }
}