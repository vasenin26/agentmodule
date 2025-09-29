<?php

namespace Anymodule\Agentmodule\TaskProcessor;

use Anymodule\Agentmodule\Entity\Task;
use Anymodule\Agentmodule\Interface\ActionsFactoryInterface;
use Anymodule\Agentmodule\Interface\ChatAgentFactoryInterface;
use Anymodule\Agentmodule\Interface\ConversationFactoryInterface;
use Anymodule\Agentmodule\Interface\GPTProcessorInterface;
use Anymodule\Agentmodule\Interface\ProcessHandlerInterface;
use Anymodule\Agentmodule\Interface\TaskStorageProviderInterface;
use Anymodule\Agentmodule\Interface\Tools\ToolServiceFactoryInterface;
use Anymodule\Agentmodule\Interface\Tools\ToolsProvider;
use Anymodule\Agentmodule\Services\ActionRunner;
use Anymodule\Agentmodule\Services\RepositoryService\RepositoryProvider;
use Anymodule\Agentmodule\Tools\Tasks\AddTasks;
use Anymodule\Agentmodule\Tools\Tasks\TasksStorage;
use Anymodule\Agentmodule\Utils\Log;
use Anymodule\Agentmodule\Utils\TokenCounter;
use Vasenin26\Conversation\Messages\DisappearingMessage;

final readonly class CodeProcessor implements \Anymodule\Agentmodule\Interface\Task\TaskProcessor
{

    public function __construct(
        private ToolServiceFactoryInterface  $toolsFactory,
        private ChatAgentFactoryInterface    $chatFactory,
        private ConversationFactoryInterface $conversationFactory,
        private TaskStorageProviderInterface $taskStorageProvider,
        private ActionsFactoryInterface      $actionsFactory,
    )
    {
    }

    public function process(Task $task, ProcessHandlerInterface $processHandler): void
    {
        $tokenCounter = new TokenCounter();

        $taskStorage = $this->taskStorageProvider->getTaskStorage($task->conversationId);
        $conversation = $this->conversationFactory->handledConversation($task->messages, $processHandler);
        $workBranch = $this->getTaskBranch($task);
        $repositoryProvider = new RepositoryProvider(
            branch: $workBranch,
            reposFolder: $this->getTmpTaskFolder($task),
        );

        $editorTools = $this->getEditorTools($task, $taskStorage, $repositoryProvider);

        $this->getActionRunner($taskStorage, $editorTools)->run($conversation, $tokenCounter);

        $conversation->addMessage(new DisappearingMessage("Check task list with tool before start"));

        $result = $this->chatFactory->createChat($editorTools)
            ->process($conversation, $processHandler, $task->resultRequired);

        foreach ($repositoryProvider->getProvidedRepositories() as $repo) {
            try {
                if ($repo->hasChanges()) {
                    $branch = $repo->getCurrentBranchName();
                    $repo->addAllChanges();
                    $repo->commit($result->answer ?? 'without comment');
                    $repo->push($branch, ['--set-upstream', 'origin']);
                }
            } catch (\Throwable $exception) {
                Log::warning($exception->getMessage());
            }
        }

        $processHandler->handle($result);
    }

    private function getTmpTaskFolder(Task $task): string
    {
        return 'task_' . $task->id;
    }

    private function getTaskBranch(Task $task): string
    {
        return 'agent/task' . $task->id;
    }

    private function getActionRunner(TasksStorage $tasksStorage, ToolsProvider $editorTools): ActionRunner
    {
        $addTasksTool = new AddTasks($tasksStorage);

        return new ActionRunner([
            'search-relevant-files' => $this->actionsFactory->createSearchRelevantFiles(),
            'plane-tasks' => $this->actionsFactory->createTaskPlanner($addTasksTool, $editorTools),
        ]);
    }

    private function getEditorTools(Task $task, TasksStorage $taskStorage, RepositoryProvider $repositoryProvider): ToolsProvider
    {
        $toolsBuilder = $this->toolsFactory->createToolsBuilderWithRepository($repositoryProvider);

        if ($task->projectId) {
            $toolsBuilder->withProject($task->projectId);
        }

        $toolsBuilder->withGit();
        $toolsBuilder->withEditor();
        $toolsBuilder->withTasks($taskStorage);

        return $toolsBuilder->build();
    }
}