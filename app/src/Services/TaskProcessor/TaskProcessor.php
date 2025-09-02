<?php

namespace Anymodule\Agentmodule\Services\TaskProcessor;

use Anymodule\Agentmodule\Entity\Task;
use Anymodule\Agentmodule\Interface\ChatFactoryInterface;
use Anymodule\Agentmodule\Interface\TaskApi;
use Anymodule\Agentmodule\Interface\ToolServiceFactoryInterface;

class TaskProcessor implements \Anymodule\Agentmodule\Interface\TaskProcessor
{
    public function __construct(
        private ToolServiceFactoryInterface $toolsFactory,
        private ChatFactoryInterface $chatFactory,
        private TaskApi $api,
    )
    {
    }

    public function process(Task $task): void
    {
        $tools = $this->toolsFactory->withMainTools();
        $chat = $this->chatFactory->createChat($tools);
        $result = $chat->process($task->messages);

        $this->api->sendResult($task->id, $result);
    }
}