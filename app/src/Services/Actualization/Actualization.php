<?php

namespace Anymodule\Agentmodule\Services\Actualization;

use Anymodule\Agentmodule\Actions\ProcessChat;
use Anymodule\Agentmodule\Entity\ProcessingResult;
use Anymodule\Agentmodule\Entity\Task;
use Anymodule\Agentmodule\Interface\LLMFactoryInterface;
use Anymodule\Agentmodule\Interface\TaskStorageProviderInterface;
use Anymodule\Agentmodule\Interface\Tools\ToolServiceFactoryInterface;
use Anymodule\Agentmodule\Services\Actualization\Actions\SearchRelevantFiles;
use Vasenin26\Conversation\Interface\ConversationFactoryInterface;
use Vasenin26\Conversation\Messages\ServiceMessage;

class Actualization implements \Anymodule\Agentmodule\Interface\Task\TaskProcessor
{
    private $actions;

    public function __construct(
        private ToolServiceFactoryInterface  $toolsFactory,
        private LLMFactoryInterface          $chatFactory,
        private ConversationFactoryInterface $conversationFactory,
        private TaskStorageProviderInterface $taskStorageProvider,
    )
    {
        $this->actions = [
           'search-relevant-files' => new SearchRelevantFiles(),
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

        $toolsBuilder = $this->toolsFactory->createToolsBuilder();

        if ($task->projectId) {
            $toolsBuilder->withProject($task->projectId);
            $toolsBuilder->withGit();
        }

        $taskStorage = $this->taskStorageProvider->getTaskStorage($task->conversationId);
        $toolsBuilder->withTasks($taskStorage);
        $tools = $toolsBuilder->build();
        $defaultProcessor = new ProcessChat($tools, $processHandler, $task->resultRequired);

        return $defaultProcessor->execute($conversation);
    }
}