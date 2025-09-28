<?php

namespace Anymodule\Agentmodule\TaskProcessor;

use Anymodule\Agentmodule\Actions\SearchRelevantFiles;
use Anymodule\Agentmodule\Actions\TaskPlanner;
use Anymodule\Agentmodule\Entity\Task;
use Anymodule\Agentmodule\Interface\ChatAgentFactoryInterface;
use Anymodule\Agentmodule\Interface\ConversationFactoryInterface;
use Anymodule\Agentmodule\Interface\GPTProcessorInterface;
use Anymodule\Agentmodule\Interface\ProcessHandlerInterface;
use Anymodule\Agentmodule\Interface\TaskStorageProviderInterface;
use Anymodule\Agentmodule\Interface\Tools\ToolServiceFactoryInterface;
use Anymodule\Agentmodule\Services\ActionRunner;
use Anymodule\Agentmodule\Services\RepositoryService\RepositoryProvider;
use Anymodule\Agentmodule\Tools\Tasks\AddTasks;
use Anymodule\Agentmodule\Tools\Tasks\TasksStorage;
use Anymodule\Agentmodule\Utils\Log;
use Anymodule\Agentmodule\Utils\Mapper\ActionInformation;
use Anymodule\Agentmodule\Utils\TokenCounter;
use Vasenin26\Conversation\Messages\DisappearingMessage;

class CodeProcessor implements \Anymodule\Agentmodule\Interface\Task\TaskProcessor
{

    public function __construct(
        private ToolServiceFactoryInterface  $toolsFactory,
        private ChatAgentFactoryInterface    $chatFactory,
        private ConversationFactoryInterface $conversationFactory,
        private TaskStorageProviderInterface $taskStorageProvider,
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

        $this->getActionRunner($taskStorage)->run($conversation, $tokenCounter);

        $conversation->addMessage(new DisappearingMessage("Check task list with tool before start"));

        $result = $this->getMainProcessor($task, $repositoryProvider, $taskStorage)
            ->process($conversation, $processHandler, $task->resultRequired);

        foreach ($repositoryProvider->getProvidedRepositories() as $repo) {
            try {
                if ($repo->hasChanges()) {
                    $branch = $repo->getCurrentBranchName();
                    $repo->addAllChanges();
                    $repo->commit($result->answer);
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
        return uniqid('task' . $task->id . '_');
    }

    private function getTaskBranch(Task $task): string
    {
        return 'agent/task' . $task->id;
    }

    private function getActionRunner(TasksStorage $tasksStorage): ActionRunner
    {
        $infoMapper = new ActionInformation();
        $addTasksTool = new AddTasks($tasksStorage);

        return new ActionRunner([
            'search-relevant-files' => new SearchRelevantFiles(
                $this->chatFactory,
                $this->toolsFactory,
                $infoMapper,
            ),
            'plane-tasks' => new TaskPlanner(
                $this->chatFactory,
                $this->toolsFactory,
                $addTasksTool,
                $infoMapper,
            )
        ]);
    }

    private function getMainProcessor(
        Task               $task,
        RepositoryProvider $repositoryProvider,
        TasksStorage       $taskStorage
    ): GPTProcessorInterface
    {

        $toolsBuilder = $this->toolsFactory->createToolsBuilderWithRepository($repositoryProvider);

        if ($task->projectId) {
            $toolsBuilder->withProject($task->projectId);
        }

        $toolsBuilder->withGit();
        $toolsBuilder->withEditor();
        $toolsBuilder->withTasks($taskStorage);

        $tools = $toolsBuilder->build();

        return $this->chatFactory->createChat($tools);
    }
}