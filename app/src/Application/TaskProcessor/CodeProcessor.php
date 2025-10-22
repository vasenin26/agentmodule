<?php

namespace Anymodule\Agentmodule\Application\TaskProcessor;

use Anymodule\Agentmodule\Application\Tools\CatchContent;
use Anymodule\Agentmodule\Application\Tools\Tasks\AddTasks;
use Anymodule\Agentmodule\Entity\Context;
use Anymodule\Agentmodule\Entity\ContextConversation;
use Anymodule\Agentmodule\Entity\Task;
use Anymodule\Agentmodule\Interface\ActionRunnerFactoryInterface;
use Anymodule\Agentmodule\Interface\ActionsFactoryInterface;
use Anymodule\Agentmodule\Interface\ChatAgentFactoryInterface;
use Anymodule\Agentmodule\Interface\ConversationFactoryInterface;
use Anymodule\Agentmodule\Interface\ProcessHandlerInterface;
use Anymodule\Agentmodule\Interface\TaskStorageProviderInterface;
use Anymodule\Agentmodule\Interface\Tools\ToolServiceFactoryInterface;
use Anymodule\Agentmodule\Services\RepositoryService\RepositoryProvider;
use Vasenin26\Conversation\Messages\DisappearingMessage;
use Vasenin26\Conversation\Messages\UserMessage;

final readonly class CodeProcessor implements \Anymodule\Agentmodule\Interface\Task\TaskProcessor
{
    const CODE_WORK_PROMPT = <<<YYY
Complete the work according to the current task list.
Mark tasks as completed after completion. 
Use tool `tasks-complete` for mark task completed.
Save the task description after completion.
YYY;


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

        $toolsBuilder->withGit();
        $toolsBuilder->withTasks($taskStorage);

        $this->actionRunnerFactory->createForTask(
            $task,
            [
                'search-relevant-files' => $this->actionsFactory->createSearchRelevantFiles(),
                'plane-tasks' => $this->actionsFactory->createTaskPlanner(new AddTasks($taskStorage), $toolsBuilder->build()),
            ]
        )->run($conversation);

        if ($this->hasNoUserAnswer($conversation)) {
            $conversation->addMessage(new DisappearingMessage(self::CODE_WORK_PROMPT));
        }

        $contentTool = new CatchContent(
            'store-description',
            'Saves a description of the work done.',
            'Result success stored to storage',
        );

        $tools = $toolsBuilder->withTools([$contentTool])
            ->withRepoManagement()
            ->withEditor()
            ->build();

        $context = new Context(
            $taskStorage->list()
        );

        $agent = $this->chatFactory->createContextAgent($tools);
        $generator = $agent->execute(new ContextConversation(
            $context,
            $conversation,
        ));

        foreach ($generator as $processingResult) {
            $answer = null;

            if ($contentTool->hasContent()) {
                $answer = $contentTool->getContent();
            }

            $context->updateTask($taskStorage->list());

            $processHandler->handle($processingResult->withAnswer($answer));
        }
    }

    private function getTmpTaskFolder(Task $task): string
    {
        return 'task_' . $task->conversationId;
    }

    private function getTaskBranch(Task $task): string
    {
        return 'agent/task' . $task->conversationId;
    }

    private function hasNoUserAnswer(\Vasenin26\Conversation\Interface\Conversation $conversation): bool
    {
        $messages = $conversation->getMessages();

        if(empty($messages)) {
            return false;
        }

        $lastElementArray = array_slice($messages, -1);
        $lastElement = $lastElementArray[0];

        return !($lastElement instanceof UserMessage);
    }
}