<?php

namespace Anymodule\Agentmodule\Services\TaskProcessor;

use Anymodule\Agentmodule\Entity\LLMResult;
use Anymodule\Agentmodule\Entity\Task;
use Anymodule\Agentmodule\Interface\LLMFactoryInterface;
use Anymodule\Agentmodule\Interface\Tools\ToolServiceFactoryInterface;
use Vasenin26\Conversation\Interface\ConversationFactoryInterface;

class TaskProcessor implements \Anymodule\Agentmodule\Interface\Task\TaskProcessor
{
    public function __construct(
        private ToolServiceFactoryInterface $toolsFactory,
        private LLMFactoryInterface $chatFactory,
        private ConversationFactoryInterface $conversationFactory,
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
        $llm = $this->chatFactory->createChat($tools);
        $chat = $this->conversationFactory->fromMessages($task->messages);
        return $llm->process($chat);
    }
}