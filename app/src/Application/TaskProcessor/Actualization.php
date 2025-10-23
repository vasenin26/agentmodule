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
use Anymodule\Agentmodule\Interface\Git\GitRepoProviderInterface;
use Anymodule\Agentmodule\Interface\ProcessHandlerInterface;
use Anymodule\Agentmodule\Interface\TaskStorageProviderInterface;
use Anymodule\Agentmodule\Interface\Tools\ToolInterface;
use Anymodule\Agentmodule\Interface\Tools\ToolServiceFactoryInterface;
use Anymodule\Agentmodule\Services\RepositoryService\RepositoryProvider;
use Anymodule\Agentmodule\Utils\Mapper\ActionInformation;
use Anymodule\Agentmodule\Utils\TokenCounter;

final class Actualization implements \Anymodule\Agentmodule\Interface\Task\TaskProcessor
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

        $repositoryProvider = new RepositoryProvider(
            branch: null
        );

        $this->actionRunnerFactory->createForTask(
            $task,
            [
                'search-relevant-files' => $this->actionsFactory->createSearchRelevantFiles($repositoryProvider),
            ]
        )->run($conversation);

        $updateTool = new UpdateArticle();
        $defaultProcessor = $this->getDefaultChatProcessor($task, $updateTool, $repositoryProvider);

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
            null,
            0,
            ...$tokenCounter->get()
        ));
    }

    private function getDefaultChatProcessor(Task $task, ToolInterface $updateTool, GitRepoProviderInterface $repositoryProvider): ActionContract
    {
        $tools = $this->getTools($task, $updateTool, $repositoryProvider);
        return new ProcessChat($this->chatAgentFactory->createAgent($tools, $repositoryProvider));
    }

    private function getTools(Task $task, ToolInterface $updateTool, GitRepoProviderInterface $repositoryProvider): ToolsProviderService
    {
        $toolsBuilder = $this->toolsFactory->createToolsBuilder();

        $taskStorage = $this->taskStorageProvider->getTaskStorage($task->conversationId);
        $toolsBuilder->withTasks($taskStorage);

        if ($task->projectId) {
            $toolsBuilder->withProject($task->projectId);
            $toolsBuilder->withGit($repositoryProvider);
        }

        $toolsBuilder->withTools([
            $updateTool,
        ]);

        return $toolsBuilder->build();
    }
}