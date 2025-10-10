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
use Anymodule\Agentmodule\Interface\Tools\ToolsProviderInterface;
use Anymodule\Agentmodule\Services\ActionRunner;
use Anymodule\Agentmodule\Services\RepositoryService\RepositoryProvider;
use Anymodule\Agentmodule\Services\ToolsService\ToolsBuilder;
use Anymodule\Agentmodule\Tools\CatchContent;
use Anymodule\Agentmodule\Tools\Tasks\AddTasks;
use Anymodule\Agentmodule\Tools\Tasks\TasksStorage;
use Anymodule\Agentmodule\Utils\Log;
use Anymodule\Agentmodule\Utils\TokenCounter;
use Vasenin26\Conversation\Messages\DisappearingMessage;

final readonly class CodeProcessor implements \Anymodule\Agentmodule\Interface\Task\TaskProcessor
{
    const CODE_WORK_PROMPT = <<<YYY
Before you begin, check your task list against the tool. 
Save the task description after completion.
YYY;


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

        $toolsBuilder = $this->getEditorTools($task, $taskStorage, $repositoryProvider);

        $this->getActionRunner($taskStorage, $toolsBuilder->build())->run($conversation, $tokenCounter);

        $conversation->addMessage(new DisappearingMessage(self::CODE_WORK_PROMPT));

        $contentTool = new CatchContent(
            'store-description',
            'Saves a description of the work done.',
            'Result success stored to storage',
        );

        $tools = $this->toolsFactory->createToolsBuilder()
            ->withGit()
            ->withTools([$contentTool])
            ->build();

        $agent = $this->chatFactory->createAgent($tools);
        $generator = $agent->execute($conversation);

        foreach ($generator as $processingResult) {
            $answer = null;

            if ($contentTool->hasContent()) {
                $answer = $contentTool->getContent();
            }

            $processHandler->handle($processingResult->withAnswer($answer));
        }
    }

    private function getTmpTaskFolder(Task $task): string
    {
        return 'task_' . $task->id;
    }

    private function getTaskBranch(Task $task): string
    {
        return 'agent/task' . $task->id;
    }

    private function getActionRunner(TasksStorage $tasksStorage, ToolsProviderInterface $editorTools): ActionRunner
    {
        $addTasksTool = new AddTasks($tasksStorage);

        return new ActionRunner([
            'search-relevant-files' => $this->actionsFactory->createSearchRelevantFiles(),
            'plane-tasks' => $this->actionsFactory->createTaskPlanner($addTasksTool, $editorTools),
        ]);
    }

    private function getEditorTools(Task $task, TasksStorage $taskStorage, RepositoryProvider $repositoryProvider): ToolsBuilder
    {
        $toolsBuilder = $this->toolsFactory->createToolsBuilderWithRepository($repositoryProvider);

        if ($task->projectId) {
            $toolsBuilder->withProject($task->projectId);
        }

        $toolsBuilder->withGit();
        $toolsBuilder->withRepoManagement();
        $toolsBuilder->withEditor();
        $toolsBuilder->withTasks($taskStorage);

        return $toolsBuilder;
    }
}