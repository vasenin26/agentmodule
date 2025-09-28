<?php

declare(strict_types=1);

namespace Anymodule\Agentmodule\TaskProcessor;

use Anymodule\Agentmodule\Actions\SearchRelevantFiles;
use Anymodule\Agentmodule\Entity\Task;
use Anymodule\Agentmodule\Interface\ConversationFactoryInterface;
use Anymodule\Agentmodule\Interface\LLMFactoryInterface;
use Anymodule\Agentmodule\Interface\ProcessHandlerInterface;
use Anymodule\Agentmodule\Interface\Task\TaskProcessor;
use Anymodule\Agentmodule\Interface\TaskStorageProviderInterface;
use Anymodule\Agentmodule\Interface\Tools\ToolServiceFactoryInterface;
use Anymodule\Agentmodule\Services\ActionRunner;
use Anymodule\Agentmodule\Utils\Mapper\ActionInformation;
use Anymodule\Agentmodule\Utils\TokenCounter;

class TextProcessor implements TaskProcessor
{
    private ActionRunner $actionRunner;

    public function __construct(
        private ToolServiceFactoryInterface  $toolsFactory,
        private LLMFactoryInterface          $chatFactory,
        private ConversationFactoryInterface $conversationFactory,
        private TaskStorageProviderInterface $taskStorageProvider,
    )
    {
        $this->actionRunner = new ActionRunner([
            'search-relevant-files' => new SearchRelevantFiles($chatFactory, $toolsFactory, new ActionInformation()),
        ]);
    }

    public function process(Task $task, ProcessHandlerInterface $processHandler): void
    {
        $toolsBuilder = $this->toolsFactory->createToolsBuilder();

        if ($task->projectId) {
            $toolsBuilder->withProject($task->projectId);
            $toolsBuilder->withGit();
        }

        $taskStorage = $this->taskStorageProvider->getTaskStorage($task->conversationId);
        $toolsBuilder->withTasks($taskStorage);

        $tools = $toolsBuilder->build();
        $llm = $this->chatFactory->createChat($tools);
        $chat = $this->conversationFactory->handledConversation($task->messages, $processHandler);

        $this->actionRunner->run($chat, new TokenCounter());

        $result = $llm->process($chat, $processHandler, $task->resultRequired);

        $processHandler->handle($result);
    }
}