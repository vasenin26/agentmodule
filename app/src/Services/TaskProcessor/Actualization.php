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
use Anymodule\Agentmodule\Interface\Tools\ToolInterface;
use Anymodule\Agentmodule\Interface\Tools\ToolServiceFactoryInterface;
use Anymodule\Agentmodule\Services\ToolsService\ToolsService;
use Anymodule\Agentmodule\Tools\Utils\UpdateArticle;
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

        $updateTool = new UpdateArticle();
        $defaultProcessor = $this->getDefaultChatProcessor($task, $updateTool);

        foreach ($defaultProcessor->execute($conversation) as $result) {
            if ($result->completed) {
                $tokenCounter->combine($result);
            }
        }

        return new ProcessingResult(
            true,
            $updateTool->getContent(),
            $conversation,
            ...$tokenCounter->get()
        );
    }

    private function getDefaultChatProcessor(Task $task, ToolInterface $updateTool): ActionContract
    {
        $tools = $this->getTools($task, $updateTool);
        return new ProcessChat($this->chatAgentFactory->createAgent($tools));
    }

    private function getTools(Task $task, ToolInterface $updateTool): ToolsService
    {
        $toolsBuilder = $this->toolsFactory->createToolsBuilder();

        $taskStorage = $this->taskStorageProvider->getTaskStorage($task->conversationId);
        $toolsBuilder->withTasks($taskStorage);

        if ($task->projectId) {
            $toolsBuilder->withProject($task->projectId);
            $toolsBuilder->withGit();
        }

        $toolsBuilder->withTools([
            'update-article' => $updateTool,
        ]);

        return $toolsBuilder->build();
    }
}