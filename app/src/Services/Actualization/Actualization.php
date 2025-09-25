<?php

namespace Anymodule\Agentmodule\Services\Actualization;

use Anymodule\Agentmodule\Actions\ProcessChat;
use Anymodule\Agentmodule\Actions\SearchRelevantFiles;
use Anymodule\Agentmodule\Entity\ProcessingResult;
use Anymodule\Agentmodule\Entity\Task;
use Anymodule\Agentmodule\Interface\ChatAgentFactoryInterface;
use Anymodule\Agentmodule\Interface\TaskStorageProviderInterface;
use Anymodule\Agentmodule\Interface\Tools\ToolServiceFactoryInterface;
use Anymodule\Agentmodule\Services\ToolsService\ToolsService;
use Vasenin26\Conversation\Interface\ConversationFactoryInterface;
use Vasenin26\Conversation\Messages\ServiceMessage;

class Actualization implements \Anymodule\Agentmodule\Interface\Task\TaskProcessor
{
    private $actions;

    public function __construct(
        private ToolServiceFactoryInterface  $toolsFactory,
        private ConversationFactoryInterface $conversationFactory,
        private TaskStorageProviderInterface $taskStorageProvider,
        private ChatAgentFactoryInterface    $chatAgentFactory,
    )
    {
        $this->actions = [
            'search-relevant-files' => new SearchRelevantFiles($chatAgentFactory, $this->toolsFactory),
        ];
    }

    public function process(Task $task, $processHandler): ProcessingResult
    {
        $conversation = $this->conversationFactory->fromMessages($task->messages);

        do {
            $awaitRun = array_diff(array_keys($this->actions), array_map(fn($m) => $m->key, (array)$conversation->getServices()));
            $currentTask = array_pop($awaitRun);

            if (!empty($currentTask)) {
                $taskProcessor = $this->actions[$currentTask] ?? null;

                if (is_null($taskProcessor)) {
                    $conversation->addMessage(new ServiceMessage($currentTask, ['error' => 'Not Found task']));
                    continue;
                }

                $taskProcessor->execute($conversation);
            }

        } while (!empty($awaitRun));

        $defaultProcessor = new ProcessChat(
            $this->getTools($task->conversationId),
            $processHandler
        );

        return $defaultProcessor->execute($conversation);
    }

    private function getTools(int $conversationId): ToolsService
    {
        $toolsBuilder = $this->toolsFactory->createToolsBuilder();

        $taskStorage = $this->taskStorageProvider->getTaskStorage($conversationId);
        $toolsBuilder->withTasks($taskStorage);

        if ($task->projectId) {
            $toolsBuilder->withProject($task->projectId);
            $toolsBuilder->withGit();
        }

        return $toolsBuilder->build();
    }
}