<?php

namespace Anymodule\Agentmodule\Services\TaskProcessor;

use Anymodule\Agentmodule\Actions\SearchRelevantFiles;
use Anymodule\Agentmodule\Entity\ProcessingResult;
use Anymodule\Agentmodule\Entity\Task;
use Anymodule\Agentmodule\Interface\LLMFactoryInterface;
use Anymodule\Agentmodule\Interface\TaskStorageProviderInterface;
use Anymodule\Agentmodule\Interface\Tools\ToolServiceFactoryInterface;
use Anymodule\Agentmodule\Services\ActionRunner;
use Anymodule\Agentmodule\Utils\TokenCounter;
use Vasenin26\Conversation\Interface\ConversationFactoryInterface;

class TextProcessor implements \Anymodule\Agentmodule\Interface\Task\TaskProcessor
{
    private ActionRunner $actionRunner;

    public function __construct(
        private ToolServiceFactoryInterface $toolsFactory,
        private LLMFactoryInterface $chatFactory,
        private ConversationFactoryInterface $conversationFactory,
        private TaskStorageProviderInterface $taskStorageProvider,
    )
    {
        $this->actionRunner = new ActionRunner([
            'search-relevant-files' => new SearchRelevantFiles($chatFactory, $toolsFactory),
        ]);
    }

    public function process(Task $task, $processHandler): ProcessingResult
    {
        $toolsBuilder = $this->toolsFactory->createToolsBuilder();

        if($task->projectId) {
            $toolsBuilder->withProject($task->projectId);
            $toolsBuilder->withGit();
        }

        $taskStorage = $this->taskStorageProvider->getTaskStorage($task->conversationId);
        $toolsBuilder->withTasks($taskStorage);

        $tools = $toolsBuilder->build();
        $llm = $this->chatFactory->createChat($tools);
        $chat = $this->conversationFactory->fromMessages($task->messages);

        $this->actionRunner->run($chat, new TokenCounter());

        return $llm->process($chat, $processHandler, $task->resultRequired);
    }
}