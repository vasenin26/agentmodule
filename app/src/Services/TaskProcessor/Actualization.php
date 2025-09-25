<?php

namespace Anymodule\Agentmodule\Services\TaskProcessor;

use Anymodule\Agentmodule\Actions\ProcessChat;
use Anymodule\Agentmodule\Actions\SearchRelevantFiles;
use Anymodule\Agentmodule\Entity\ProcessingResult;
use Anymodule\Agentmodule\Entity\Task;
use Anymodule\Agentmodule\Interface\ActionContract;
use Anymodule\Agentmodule\Interface\ChatAgentFactoryInterface;
use Anymodule\Agentmodule\Interface\ConversationFactoryInterface;
use Anymodule\Agentmodule\Interface\TaskStorageProviderInterface;
use Anymodule\Agentmodule\Interface\Tools\ToolServiceFactoryInterface;
use Anymodule\Agentmodule\Services\ToolsService\ToolsService;
use Anymodule\Agentmodule\Utils\TokenCounter;
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
        $conversation = $this->conversationFactory->handledConversation($task->messages, $processHandler);
        $tokenCounter = new TokenCounter();

        do {
            $awaitRun = array_diff(array_keys($this->actions), array_map(fn($m) => $m->key, (array)$conversation->getServices()));
            $currentTask = array_pop($awaitRun);

            if (!empty($currentTask)) {
                $taskProcessor = $this->actions[$currentTask] ?? null;

                if (is_null($taskProcessor)) {
                    $conversation->addMessage(new ServiceMessage($currentTask, ['error' => 'Not Found task']));
                    continue;
                }

                foreach ($taskProcessor->execute($conversation) as $result) {
                    if ($result->completed) {
                        $conversation->addMessage(new ServiceMessage($currentTask, ['message' => $result->answer]));
                        $tokenCounter->combine($result);

                        foreach ($result->conversation->getMessages() as $message) {
                            $conversation->addMessage($message);
                        }
                    }
                }
            }
        } while (!empty($awaitRun));

        $defaultProcessor = $this->getDefaultChatProcessor($task);

        foreach ($defaultProcessor->execute($conversation) as $result) {
            if ($result->completed) {
                $tokenCounter->combine($result);
            }
        }

        return new ProcessingResult(
            true,
            'Actualization completed',
            $conversation,
            ...$tokenCounter->get()
        );
    }

    private function getTools(Task $task): ToolsService
    {
        $toolsBuilder = $this->toolsFactory->createToolsBuilder();

        $taskStorage = $this->taskStorageProvider->getTaskStorage($task->conversationId);
        $toolsBuilder->withTasks($taskStorage);

        if ($task->projectId) {
            $toolsBuilder->withProject($task->projectId);
            $toolsBuilder->withGit();
        }

        return $toolsBuilder->build();
    }

    private function getDefaultChatProcessor(Task $task): ActionContract
    {
        $tools = $this->getTools($task);
        return new ProcessChat($this->chatAgentFactory->createAgent($tools));
    }
}