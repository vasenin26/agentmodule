<?php

namespace Anymodule\Agentmodule\Services\TaskProcessor;

use Anymodule\Agentmodule\Entity\Task;
use Anymodule\Agentmodule\Interface\ConversationFactoryInterface;
use Anymodule\Agentmodule\Interface\LLMFactoryInterface;
use Anymodule\Agentmodule\Interface\ProcessHandlerInterface;
use Anymodule\Agentmodule\Interface\TaskStorageProviderInterface;
use Anymodule\Agentmodule\Interface\Tools\ToolServiceFactoryInterface;
use Anymodule\Agentmodule\Services\RepositoryService\RepositoryProvider;
use Anymodule\Agentmodule\Utils\Log;

class CodeProcessor implements \Anymodule\Agentmodule\Interface\Task\TaskProcessor
{
    public function __construct(
        private ToolServiceFactoryInterface  $toolsFactory,
        private LLMFactoryInterface          $chatFactory,
        private ConversationFactoryInterface $conversationFactory,
        private TaskStorageProviderInterface $taskStorageProvider,
    )
    {
    }

    public function process(Task $task, ProcessHandlerInterface $processHandler): void
    {
        $workBranch = $this->getTaskBranch($task);

        $repositoryProvider = new RepositoryProvider(
            branch: $workBranch,
            reposFolder: $this->getTmpTaskFolder($task),
        );

        $toolsBuilder = $this->toolsFactory->createToolsBuilderWithRepository($repositoryProvider);

        if ($task->projectId) {
            $toolsBuilder->withProject($task->projectId);
        }

        $taskStorage = $this->taskStorageProvider->getTaskStorage($task->conversationId);

        $toolsBuilder->withGit();
        $toolsBuilder->withEditor();
        $toolsBuilder->withTasks($taskStorage);

        $tools = $toolsBuilder->build();
        $llm = $this->chatFactory->createChat($tools);
        $chat = $this->conversationFactory->handledConversation($task->messages, $processHandler);

        $result = $llm->process($chat, $processHandler, $task->resultRequired);

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
}