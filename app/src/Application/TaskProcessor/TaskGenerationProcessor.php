<?php

namespace Anymodule\Agentmodule\Application\TaskProcessor;

use Anymodule\Agentmodule\Application\Actions\TipsProcessor;
use Anymodule\Agentmodule\Application\Tools\Utils\UpdateTask;
use Anymodule\Agentmodule\Application\ToolsService\ToolsProviderService;
use Anymodule\Agentmodule\Entity\Task;
use Anymodule\Agentmodule\Interface\ActionContract;
use Anymodule\Agentmodule\Interface\Factory\ActionRunnerFactoryInterface;
use Anymodule\Agentmodule\Interface\Factory\ActionsFactoryInterface;
use Anymodule\Agentmodule\Interface\Factory\ChatAgentFactoryInterface;
use Anymodule\Agentmodule\Interface\Factory\ConversationFactoryInterface;
use Anymodule\Agentmodule\Interface\Git\GitRepoProviderInterface;
use Anymodule\Agentmodule\Interface\ProcessHandlerInterface;
use Anymodule\Agentmodule\Interface\Tools\ToolInterface;
use Anymodule\Agentmodule\Interface\Tools\ToolServiceFactoryInterface;
use Anymodule\Agentmodule\Services\RepositoryService\RepositoryProvider;

final class TaskGenerationProcessor implements \Anymodule\Agentmodule\Interface\Task\TaskProcessor
{

    const TASK_PROMPT = <<<TTT
Тебе нужно собрать необходимую информацию и сохранить в хранилище описание задачи и заголовок.
Сохрани полное описание задачи в хранилище используя `update-task`. 
После успешного сохранения в хранилище, напиши короткую сводку о сохранённой задаче.
TTT;

    public function __construct(
        private ToolServiceFactoryInterface  $toolsFactory,
        private ConversationFactoryInterface $conversationFactory,
        private ChatAgentFactoryInterface    $chatAgentFactory,
        private ActionRunnerFactoryInterface $actionRunnerFactory,
        private ActionsFactoryInterface      $actionsFactory,
    )
    {
    }

    public function supports(Task $task): bool
    {
        return $task->type === 'task';
    }

    public function process(Task $task, ProcessHandlerInterface $processHandler): void
    {
        $conversation = $this->conversationFactory->handledConversation($task->messages, $processHandler);

        $repositoryProvider = new RepositoryProvider(
            branch: null
        );

        $this->actionRunnerFactory->createForTask(
            $task,
            [
                'search-relevant-files' => $this->actionsFactory->createSearchRelevantFiles($task->projectId, $repositoryProvider),
            ]
        )->run($conversation);

        $contentTool = new UpdateTask();
        $defaultProcessor = $this->getDefaultChatProcessor($task, $contentTool, $repositoryProvider);

        foreach ($defaultProcessor->execute($conversation->conversation) as $result) {
            if ($contentTool->hasContent()) {
                $processHandler->handle($result->withAnswer($contentTool->getContent()));
                $contentTool->flush();
            } else {
                $processHandler->handle($result);
            }
        }
    }

    private function getDefaultChatProcessor(Task $task, ToolInterface $updateTool, GitRepoProviderInterface $repositoryProvider): ActionContract
    {
        $tools = $this->getTools($task, $updateTool, $repositoryProvider);
        return new TipsProcessor($this->chatAgentFactory->createAgent($tools, $repositoryProvider), self::TASK_PROMPT);
    }

    private function getTools(Task $task, ToolInterface $updateTool, GitRepoProviderInterface $repositoryProvider): ToolsProviderService
    {
        $toolsBuilder = $this->toolsFactory->createToolsBuilder();

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