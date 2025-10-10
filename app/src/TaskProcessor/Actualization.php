<?php

namespace Anymodule\Agentmodule\TaskProcessor;

use Anymodule\Agentmodule\Actions\ProcessChat;
use Anymodule\Agentmodule\Actions\SearchRelevantFiles;
use Anymodule\Agentmodule\Entity\ProcessingResult;
use Anymodule\Agentmodule\Entity\Task;
use Anymodule\Agentmodule\Interface\ActionContract;
use Anymodule\Agentmodule\Interface\ChatAgentFactoryInterface;
use Anymodule\Agentmodule\Interface\ConversationFactoryInterface;
use Anymodule\Agentmodule\Interface\ProcessHandlerInterface;
use Anymodule\Agentmodule\Interface\TaskStorageProviderInterface;
use Anymodule\Agentmodule\Interface\Tools\ToolInterface;
use Anymodule\Agentmodule\Interface\Tools\ToolServiceFactoryInterface;
use Anymodule\Agentmodule\Services\ActionRunner;
use Anymodule\Agentmodule\Services\ToolsService\ToolsProviderService;
use Anymodule\Agentmodule\Tools\Utils\UpdateArticle;
use Anymodule\Agentmodule\Utils\Mapper\ActionInformation;
use Anymodule\Agentmodule\Utils\TokenCounter;

class Actualization implements \Anymodule\Agentmodule\Interface\Task\TaskProcessor
{
    private ActionRunner $actionRunner;

    public function __construct(
        private ToolServiceFactoryInterface  $toolsFactory,
        private ConversationFactoryInterface $conversationFactory,
        private TaskStorageProviderInterface $taskStorageProvider,
        private ChatAgentFactoryInterface    $chatAgentFactory,
    )
    {
        $this->actionRunner = new ActionRunner([
            'search-relevant-files' => new SearchRelevantFiles($chatAgentFactory, $this->toolsFactory, new ActionInformation()),
        ]);
    }

    public function process(Task $task, ProcessHandlerInterface $processHandler): void
    {
        $conversation = $this->conversationFactory->handledConversation($task->messages, $processHandler);
        $tokenCounter = new TokenCounter();

        $this->actionRunner->run($conversation, $tokenCounter);

        $updateTool = new UpdateArticle();
        $defaultProcessor = $this->getDefaultChatProcessor($task, $updateTool);

        foreach ($defaultProcessor->execute($conversation) as $result) {
            if ($result->completed) {
                $tokenCounter->combine($result);
            }
        }

        $processHandler->handle(new ProcessingResult(
            true,
            $updateTool->getContent(),
            $conversation,
            0,
            ...$tokenCounter->get()
        ));
    }

    private function getDefaultChatProcessor(Task $task, ToolInterface $updateTool): ActionContract
    {
        $tools = $this->getTools($task, $updateTool);
        return new ProcessChat($this->chatAgentFactory->createAgent($tools));
    }

    private function getTools(Task $task, ToolInterface $updateTool): ToolsProviderService
    {
        $toolsBuilder = $this->toolsFactory->createToolsBuilder();

        $taskStorage = $this->taskStorageProvider->getTaskStorage($task->conversationId);
        $toolsBuilder->withTasks($taskStorage);

        if ($task->projectId) {
            $toolsBuilder->withProject($task->projectId);
            $toolsBuilder->withGit();
        }

        $toolsBuilder->withTools([
            $updateTool,
        ]);

        return $toolsBuilder->build();
    }
}