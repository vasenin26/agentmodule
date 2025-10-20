<?php

namespace Anymodule\Agentmodule\Application\TaskProcessor;

use Anymodule\Agentmodule\Application\ActionRunner;
use Anymodule\Agentmodule\Application\Actions\ProcessChat;
use Anymodule\Agentmodule\Application\Actions\SearchRelevantFiles;
use Anymodule\Agentmodule\Application\Tools\Tasks\AddTasks;
use Anymodule\Agentmodule\Application\Tools\Utils\UpdateArticle;
use Anymodule\Agentmodule\Application\ToolsService\ToolsProviderService;
use Anymodule\Agentmodule\Entity\ProcessingResult;
use Anymodule\Agentmodule\Entity\Task;
use Anymodule\Agentmodule\Interface\ActionContract;
use Anymodule\Agentmodule\Interface\ActionRunnerFactoryInterface;
use Anymodule\Agentmodule\Interface\ActionsFactoryInterface;
use Anymodule\Agentmodule\Interface\ChatAgentFactoryInterface;
use Anymodule\Agentmodule\Interface\ConversationFactoryInterface;
use Anymodule\Agentmodule\Interface\ProcessHandlerInterface;
use Anymodule\Agentmodule\Interface\TaskStorageProviderInterface;
use Anymodule\Agentmodule\Interface\Tools\ToolInterface;
use Anymodule\Agentmodule\Interface\Tools\ToolServiceFactoryInterface;
use Anymodule\Agentmodule\Utils\Mapper\ActionInformation;
use Anymodule\Agentmodule\Utils\TokenCounter;

class Actualization implements \Anymodule\Agentmodule\Interface\Task\TaskProcessor
{
    public function __construct(
        private ToolServiceFactoryInterface  $toolsFactory,
        private ConversationFactoryInterface $conversationFactory,
        private TaskStorageProviderInterface $taskStorageProvider,
        private ChatAgentFactoryInterface    $chatAgentFactory,
        private ActionRunnerFactoryInterface $actionRunnerFactory,
        private ActionsFactoryInterface      $actionsFactory,
    )
    {
    }

    public function process(Task $task, ProcessHandlerInterface $processHandler): void
    {
        $conversation = $this->conversationFactory->handledConversation($task->messages, $processHandler);
        $tokenCounter = new TokenCounter();

        $this->actionRunnerFactory->createForTask(
            $task,
            [
                'search-relevant-files' => $this->actionsFactory->createSearchRelevantFiles(),
            ]
        )->run($conversation);

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
            null,
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