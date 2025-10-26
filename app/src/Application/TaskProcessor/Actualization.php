<?php

namespace Anymodule\Agentmodule\Application\TaskProcessor;

use Anymodule\Agentmodule\Application\Actions\TipsProcessor;
use Anymodule\Agentmodule\Application\Tools\CatchContent;
use Anymodule\Agentmodule\Application\ToolsService\ToolsProviderService;
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

final class Actualization implements \Anymodule\Agentmodule\Interface\Task\TaskProcessor
{
    const PROMPT = <<<AAA
You need to gather the necessary information and save the new article content to the repository.
Save the full article description to the repository using the `store` command.

If the original article contains a placeholder, replace it with a relevant value.
```
For example:
---
[available actions]
---
Replace with:
---
- addition
- deletion
---
````

After successfully saving to the repository, write a brief description of the article update.
AAA;

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

    public function supports(Task $task): bool
    {
        return $task->type === 'actualization';
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
                'search-relevant-files' => $this->actionsFactory->createSearchRelevantFiles($repositoryProvider),
            ]
        )->run($conversation);

        $contentTool = new CatchContent(
            'store',
            'Сохраняет содержимое статьи в хранилище.',
            'Статья сохранёна.',
        );

        $defaultProcessor = $this->getDefaultChatProcessor($task, $contentTool, $repositoryProvider);

        foreach ($defaultProcessor->execute($conversation->conversation) as $result) {
            if ($contentTool->hasContent()) {
                $processHandler->handle($result->withAnswer($contentTool->getContent()));
            } else {
                $processHandler->handle($result);
            }
        }
    }

    private function getDefaultChatProcessor(Task $task, ToolInterface $updateTool, GitRepoProviderInterface $repositoryProvider): ActionContract
    {
        $tools = $this->getTools($task, $updateTool, $repositoryProvider);
        return new TipsProcessor($this->chatAgentFactory->createAgent($tools, $repositoryProvider), self::PROMPT);
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