<?php

declare(strict_types=1);

namespace Anymodule\Agentmodule\TaskProcessor;

use Anymodule\Agentmodule\Actions\SearchRelevantFiles;
use Anymodule\Agentmodule\Entity\ProcessingResult;
use Anymodule\Agentmodule\Entity\Task;
use Anymodule\Agentmodule\Interface\ConversationFactoryInterface;
use Anymodule\Agentmodule\Interface\LLMFactoryInterface;
use Anymodule\Agentmodule\Interface\ProcessHandlerInterface;
use Anymodule\Agentmodule\Interface\Task\TaskProcessor;
use Anymodule\Agentmodule\Interface\TaskStorageProviderInterface;
use Anymodule\Agentmodule\Interface\Tools\ToolServiceFactoryInterface;
use Anymodule\Agentmodule\Services\ActionRunner;
use Anymodule\Agentmodule\Tools\CatchContent;
use Anymodule\Agentmodule\Utils\Mapper\ActionInformation;
use Anymodule\Agentmodule\Utils\TokenCounter;
use Vasenin26\Conversation\Chat;
use Vasenin26\Conversation\Messages\GitFileMessage;

class TextProcessor implements TaskProcessor
{
    private ActionRunner $actionRunner;

    public function __construct(
        private ToolServiceFactoryInterface  $toolsFactory,
        private LLMFactoryInterface          $chatFactory,
        private ConversationFactoryInterface $conversationFactory,
        private TaskStorageProviderInterface $taskStorageProvider,
    )
    {
        $this->actionRunner = new ActionRunner([
            'search-relevant-files' => new SearchRelevantFiles($chatFactory, $toolsFactory, new ActionInformation()),
        ]);
    }

    public function process(Task $task, ProcessHandlerInterface $processHandler): void
    {
        $toolsBuilder = $this->toolsFactory->createToolsBuilder();

        if ($task->projectId) {
            $toolsBuilder->withProject($task->projectId);
            $toolsBuilder->withGit();
        }

        $taskStorage = $this->taskStorageProvider->getTaskStorage($task->conversationId);
        $toolsBuilder->withTasks($taskStorage);

        $tools = $toolsBuilder->build();
        $chat = $this->conversationFactory->handledConversation($task->messages, $processHandler);

        $this->actionRunner->run($chat, new TokenCounter());

        $contentTool = new CatchContent(
            'store-result',
            'Store result to storage',
            'Result success stored to storage',
        );

        $tools = $toolsBuilder->withTools([$contentTool])->build();

        $agent = $this->chatFactory->createAgent($tools);
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