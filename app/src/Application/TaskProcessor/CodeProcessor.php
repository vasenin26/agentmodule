<?php

namespace Anymodule\Agentmodule\Application\TaskProcessor;

use Anymodule\Agentmodule\Application\Logger\Log;
use Anymodule\Agentmodule\Application\Tools\CatchContent;
use Anymodule\Agentmodule\Entity\Context;
use Anymodule\Agentmodule\Entity\ContextConversation;
use Anymodule\Agentmodule\Entity\Task;
use Anymodule\Agentmodule\Interface\Factory\ActionRunnerFactoryInterface;
use Anymodule\Agentmodule\Interface\Factory\ActionsFactoryInterface;
use Anymodule\Agentmodule\Interface\Factory\ChatAgentFactoryInterface;
use Anymodule\Agentmodule\Interface\Factory\ConversationFactoryInterface;
use Anymodule\Agentmodule\Interface\ProcessHandlerInterface;
use Anymodule\Agentmodule\Interface\Storage\TaskStorageProviderInterface;
use Anymodule\Agentmodule\Interface\Tools\ToolServiceFactoryInterface;
use Anymodule\Agentmodule\Services\RepositoryService\RepositoryProvider;
use Vasenin26\Conversation\Messages\DisappearingMessage;

final readonly class CodeProcessor implements \Anymodule\Agentmodule\Interface\Task\TaskProcessor
{
    const CODE_WORK_PROMPT = <<<YYY
Complete the work according to the current task list.
Mark tasks as completed after completion. 
Use tool `tasks-complete` for mark task completed.
Save the task description after completion.
YYY;

    const REMEMBER_FINISH_TASKS = <<<TTT
You have uncompleted tasks.
Complete all tasks.
Mark completed tasks using `tasks-complete` tool.
TTT;


    public function __construct(
        private ToolServiceFactoryInterface  $toolsFactory,
        private ChatAgentFactoryInterface    $chatFactory,
        private ConversationFactoryInterface $conversationFactory,
        private TaskStorageProviderInterface $taskStorageProvider,
        private ActionRunnerFactoryInterface $actionRunnerFactory,
        private ActionsFactoryInterface      $actionsFactory,
    )
    {
    }

    public function supports(Task $task): bool
    {
        return $task->type === 'code';
    }

    public function process(Task $task, ProcessHandlerInterface $processHandler): void
    {
        $taskStorage = $this->taskStorageProvider->createStorageFromContext(new Context($task->context['tasks'] ?? []));
        $conversation = $this->conversationFactory->handledConversation($task->messages, $processHandler);
        $workBranch = $this->getTaskBranch($task);

        $repositoryProvider = new RepositoryProvider(
            branch: $workBranch,
            reposFolder: $this->getTmpTaskFolder($task),
        );

        $toolsBuilder = $this->toolsFactory->createToolsBuilderWithRepository($repositoryProvider);

        if ($task->projectId) {
            $toolsBuilder->withProject($task->projectId);
        }

        $contentTool = new CatchContent(
            'store-description',
            'Saves a description of the work done.',
            'Result success stored to storage',
        );

        $tools = $toolsBuilder->withGit($repositoryProvider)
            ->withTasks($taskStorage)
            ->withTools([$contentTool])
            ->withRepoManagement($repositoryProvider)
            ->withEditor($repositoryProvider)
            ->withTerminal()
            ->build();

        $this->actionRunnerFactory->createForTask(
            $task,
            [
//                'search-relevant-files' => $this->actionsFactory->createSearchRelevantFiles($task->projectId, $repositoryProvider),
                'plane-tasks' => $this->actionsFactory->createTaskPlanner($task->projectId, $taskStorage, $tools, $repositoryProvider),
            ]
        )->run($conversation);

        $needAnswer = $conversation->hasNoUserAnswer();

        if (!$needAnswer) {
            $conversation->addMessage(new DisappearingMessage(self::CODE_WORK_PROMPT));
        }

        $context = new Context(
            $taskStorage->list()
        );

        $agent = $this->chatFactory->createContextAgent($tools, $repositoryProvider);

        do {
            $finished = true;

            $generator = $agent->execute(new ContextConversation(
                $context,
                $conversation->conversation,
            ));

            foreach ($generator as $processingResult) {
                $answer = null;

                if ($contentTool->hasContent()) {
                    $answer = $contentTool->getContent();
                }

                $context->updateTask($taskStorage->list());

                $processHandler->handle($processingResult->withAnswer($answer));
            }

            if (!$needAnswer) {
                if ($taskStorage->getStats()['remaining'] > 0) {
                    $finished = false;

                    Log::info("Remember uncompleted tasks.");
                    $conversation->addMessage(new DisappearingMessage(self::REMEMBER_FINISH_TASKS));
                }
            }

        } while (!$finished);
    }

    private function getTmpTaskFolder(Task $task): string
    {
        return 'task_' . $task->conversationId;
    }

    private function getTaskBranch(Task $task): string
    {
        return 'agent/task' . $task->conversationId;
    }
}