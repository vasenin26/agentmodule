<?php

declare(strict_types=1);

namespace Anymodule\Agentmodule\Application\TaskProcessor;

use Anymodule\Agentmodule\Application\Tools\CatchContent;
use Anymodule\Agentmodule\Application\Tools\Terminal\Run;
use Anymodule\Agentmodule\Entity\ProcessingResult;
use Anymodule\Agentmodule\Entity\Task;
use Anymodule\Agentmodule\Interface\Factory\ActionRunnerFactoryInterface;
use Anymodule\Agentmodule\Interface\Factory\ActionsFactoryInterface;
use Anymodule\Agentmodule\Interface\Factory\ChatAgentFactoryInterface;
use Anymodule\Agentmodule\Interface\Factory\ConversationFactoryInterface;
use Anymodule\Agentmodule\Interface\ProcessHandlerInterface;
use Anymodule\Agentmodule\Interface\Storage\TaskStorageProviderInterface;
use Anymodule\Agentmodule\Interface\Task\TaskProcessor;
use Anymodule\Agentmodule\Interface\Tools\ToolServiceFactoryInterface;
use Anymodule\Agentmodule\Services\RepositoryService\RepositoryProvider;
use Vasenin26\Conversation\Message;
use Vasenin26\Conversation\Messages\AssistantMessage;
use Vasenin26\Conversation\Messages\UserMessage;

final class TerminalProcessor implements TaskProcessor
{
    public function __construct(
        private ToolServiceFactoryInterface  $toolsFactory,
        private ConversationFactoryInterface $conversationFactory,
    )
    {
    }

    public function supports(Task $task): bool
    {
        return $task->type === 'terminal';
    }

    public function process(Task $task, ProcessHandlerInterface $processHandler): void
    {
        $toolsBuilder = $this->toolsFactory->createToolsBuilder();
        $tools = $toolsBuilder->withTerminal()->build();

        $chat = $this->conversationFactory->fromMessages($task->messages);
        $messages = $chat->getMessages();
        /**
         * @var $lastMessage Message|null
         */
        $lastMessage = end($messages);

        var_dump($lastMessage);

        if (!is_null($lastMessage) && $lastMessage->getType() === UserMessage::TYPE) {
            $commandResult = $tools->callTool(Run::NAME, json_encode([
                'command' => $lastMessage->getContent()['content'] ?? null,
            ]));

            var_dump($commandResult);

            $chat->addMessage(new AssistantMessage(
                $commandResult->status ?
                    $commandResult->payload['stdout'] ?? 'empty stdout' :
                    $commandResult->payload['stderr'] ?? 'empty stderr',
                []
            ));
        }

        $processHandler->handle(new ProcessingResult(
            completed: true,
            answer: 'ok',
            conversation: $chat,
        ));
    }
}