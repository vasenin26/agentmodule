<?php

declare(strict_types=1);

namespace Anymodule\Agentmodule\Application\TaskProcessor;

use Anymodule\Agentmodule\Application\ActionRunner;
use Anymodule\Agentmodule\Application\Actions\SearchRelevantFiles;
use Anymodule\Agentmodule\Application\Tools\CatchContent;
use Anymodule\Agentmodule\Application\Tools\Tasks\AddTasks;
use Anymodule\Agentmodule\Entity\Task;
use Anymodule\Agentmodule\Interface\ActionRunnerFactoryInterface;
use Anymodule\Agentmodule\Interface\ActionsFactoryInterface;
use Anymodule\Agentmodule\Interface\ChatAgentFactoryInterface;
use Anymodule\Agentmodule\Interface\ConversationFactoryInterface;
use Anymodule\Agentmodule\Interface\ProcessHandlerInterface;
use Anymodule\Agentmodule\Interface\Task\TaskProcessor;
use Anymodule\Agentmodule\Interface\TaskStorageProviderInterface;
use Anymodule\Agentmodule\Interface\Tools\ToolServiceFactoryInterface;
use Anymodule\Agentmodule\Services\RepositoryService\RepositoryProvider;
use Anymodule\Agentmodule\Utils\Mapper\ActionInformation;
use Anymodule\Agentmodule\Utils\TokenCounter;

final class TextProcessor implements TaskProcessor
{
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
        return true;
    }

    public function process(Task $task, ProcessHandlerInterface $processHandler): void
    {
        $toolsBuilder = $this->toolsFactory->createToolsBuilder();

        $repositoryProvider = new RepositoryProvider(
            branch: null
        );

        if ($task->projectId) {
            $toolsBuilder->withProject($task->projectId);
            $toolsBuilder->withGit($repositoryProvider);
        }

        $taskStorage = $this->taskStorageProvider->getTaskStorage($task->conversationId);
        $toolsBuilder->withTasks($taskStorage);

        $chat = $this->conversationFactory->handledConversation($task->messages, $processHandler);

        $this->actionRunnerFactory->createForTask(
            $task,
            [
                'search-relevant-files' => $this->actionsFactory->createSearchRelevantFiles($repositoryProvider),
            ]
        )->run($chat);

        $contentTool = new CatchContent(
            'store-result',
            'Store result to storage',
            'Result success stored to storage',
        );

        $tools = $toolsBuilder->withTools([$contentTool])->build();

        $agent = $this->chatFactory->createAgent($tools, $repositoryProvider);
        $generator = $agent->execute($chat);

        foreach ($generator as $processingResult) {
            $answer = null;

            if ($contentTool->hasContent()) {
                $answer = $contentTool->getContent();
            }

            $processHandler->handle($processingResult->withAnswer($answer));
        }
    }
}