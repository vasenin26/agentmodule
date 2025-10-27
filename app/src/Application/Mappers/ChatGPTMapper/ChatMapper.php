<?php

namespace Anymodule\Agentmodule\Application\Mappers\ChatGPTMapper;

use Anymodule\Agentmodule\Application\Mappers\ChatGPTMapper\Interface\OpenAIMessageMapperInterface;
use Anymodule\Agentmodule\Application\Mappers\ChatGPTMapper\Mappers\AssistantMapper;
use Anymodule\Agentmodule\Application\Mappers\ChatGPTMapper\Mappers\CallToolMapper;
use Anymodule\Agentmodule\Application\Mappers\ChatGPTMapper\Mappers\DisappearingMessageMapper;
use Anymodule\Agentmodule\Application\Mappers\ChatGPTMapper\Mappers\GitFileMapper;
use Anymodule\Agentmodule\Application\Mappers\ChatGPTMapper\Mappers\SystemMapper;
use Anymodule\Agentmodule\Application\Mappers\ChatGPTMapper\Mappers\ToolMapper;
use Anymodule\Agentmodule\Application\Mappers\ChatGPTMapper\Mappers\UserMapper;
use Anymodule\Agentmodule\Application\Mappers\ChatGPTMapper\Mappers\UserTaskMapper;
use Anymodule\Agentmodule\Application\Mappers\ChatGPTMapper\Optimizators\Editor\ReadFileOptimization;
use Anymodule\Agentmodule\Application\Tools\Editor\ChangeLine;
use Anymodule\Agentmodule\Application\Tools\Git\ReadFile;
use Anymodule\Agentmodule\Application\Tools\Tasks\AddTasks;
use Anymodule\Agentmodule\Application\Tools\Tasks\CompleteTask;
use Anymodule\Agentmodule\Application\Tools\Tasks\ListTasks;
use Anymodule\Agentmodule\Interface\Git\GitRepoProviderInterface;
use Anymodule\Agentmodule\Interface\Tools\ToolsProviderInterface;
use Anymodule\Agentmodule\Services\OpenAIChat\DTO\OpenAiResult;
use Anymodule\Agentmodule\Services\OpenAIChat\Interface\MessageMapper;
use Anymodule\Agentmodule\Utils\Log;
use OpenAI\Responses\Chat\CreateResponse;
use Vasenin26\Conversation\Chat;
use Vasenin26\Conversation\Interface\Conversation;
use Vasenin26\Conversation\Messages\AssistantMessage;
use Vasenin26\Conversation\Messages\DisappearingMessage;
use Vasenin26\Conversation\Messages\ToolMessage;

class ChatMapper implements MessageMapper
{
    const ANY_MESSAGE_ALIVE_LIMIT = 30;

    const SHORT_TIME_LIVE_MESSAGES = [
        AddTasks::NAME,
        ListTasks::NAME,
        CompleteTask::NAME,
        ChangeLine::NAME,
    ];

    private $mappers = [];
    private $toolOptimisers = [];

    public function __construct(
        private OpenAIMessageMapperInterface $AIMessageProcessor,
        GitRepoProviderInterface             $repositoryProvider,
        ?ToolsProviderInterface              $toolsService = null,
    )
    {
        $this->mappers = [
            new UserMapper(),
            new UserTaskMapper(),
            new DisappearingMessageMapper(),
            new AssistantMapper(),
            new SystemMapper(),
            new ToolMapper(),
            new GitFileMapper($repositoryProvider),
        ];

        $this->toolOptimisers = [
            new ReadFileOptimization(),
        ];

        if ($toolsService !== null) {
            $this->mappers[] = new CallToolMapper($toolsService);
        }
    }

    public function mapChat(Conversation $chat): array
    {
        $chat = $this->optimizeOldToolCalls($chat);
        $messages = $this->processMessages($chat);

        return $messages;
    }

    private function processMessages(Conversation $chat): array
    {
        $chatMessages = $chat->getMessages();
        $resultMessages = [];

        while ($message = array_shift($chatMessages)) {
            if ($message instanceof AssistantMessage) {
                if (!$this->chatHasAllAnswers($message, $chatMessages)) {
                    $this->removeToolsStack($chatMessages);
                    continue;
                }
            }

            if ($message) {
                foreach ($this->mappers as $mapper) {
                    if ($mapper->supports($message)) {
                        $resultMessages[] = $mapper->map($message);
                        break;
                    }
                }
            }
        }

        return $resultMessages;
    }


    public function prepareAssistantMessage(CreateResponse $result): OpenAiResult
    {
        return $this->AIMessageProcessor->prepareAssistantMessage($result);
    }

    private function chatHasAllAnswers(AssistantMessage $assistantMessage, array $chatMessages): bool
    {
        if (empty($assistantMessage->toolCallsArray)) {
            return true;
        }

        $toolCalls = array_map(fn($t) => $t['id'], $assistantMessage->toolCallsArray);

        foreach ($chatMessages as $message) {
            if ($message instanceof ToolMessage) {
                if (in_array($message->id, $toolCalls)) {
                    $toolCalls = array_values(array_filter($toolCalls, fn($item) => $item !== $message->id));
                } else {
                    return false;
                }
            } else {
                break;
            }
        }

        return count($toolCalls) === 0;
    }

    private function removeToolsStack(array &$chatMessages): void
    {
        $clone = array_slice($chatMessages, 0);

        do {
            $message = current($clone);

            if ($message instanceof ToolMessage) {
                array_shift($chatMessages);
            } else {
                break;
            }
        } while (next($clone));
    }

    private function optimizeOldToolCalls(Conversation $chat): Conversation
    {
        $irrelevantFunctionCalls = [];
        $result = new Chat();
        $optimisedCounter = 0;
        $removedAssistantMessagesCounter = 0;

        $oldMessagesCount = count($chat->getMessages()) - self::ANY_MESSAGE_ALIVE_LIMIT;

        if ($oldMessagesCount <= 0) {
            return $chat;
        }

        $slice = array_slice($chat->getMessages(), 0, $oldMessagesCount);
        $oldToolsIds = [];
        $fileReads = [];

        foreach ($slice as $message) {
            if ($message instanceof ToolMessage) {
                $oldToolsIds[] = $message->id;
                if ($message->success === false || in_array($message->name, self::SHORT_TIME_LIVE_MESSAGES)) {
                    $irrelevantFunctionCalls[] = $message->id;
                    continue;
                }

                if ($message->name === ReadFile::NAME) {
                    $filePath = $this->getFilePath($message);

                    foreach ($fileReads as $toolId => $uri) {
                        if ($uri === $filePath) {
                            Log::info("Found file [$uri] read duplicate");
                            if (!in_array($toolId, $irrelevantFunctionCalls)) {
                                $irrelevantFunctionCalls[] = $toolId;
                            }
                        }
                    }

                    $fileReads[$message->id] = $filePath;
                }
            }
        }

        foreach ($chat->getMessages() as $message) {
            if ($message instanceof ToolMessage) {
                if (in_array($message->id, $irrelevantFunctionCalls)) {
                    continue;
                } else if (in_array($message->id, $oldToolsIds)) {
                    foreach ($this->toolOptimisers as $optimiser) {
                        if ($optimiser->supports($message)) {
                            $message = $optimiser->optimize($message);
                            $optimisedCounter++;
                            break;
                        }
                    }
                }
            }

            if ($message instanceof AssistantMessage) {
                $message = $this->removeForgetFunctionCalls($message, $irrelevantFunctionCalls);
                if ($message === null) {
                    $removedAssistantMessagesCounter++;
                    continue;
                }
            }

            $result->addMessage($message);
        }

        if (!empty($irrelevantFunctionCalls)) {
            Log::info("Found " . count($irrelevantFunctionCalls) . " irrelevant tool calls");
        }

        if ($removedAssistantMessagesCounter > 0) {
            Log::info('Removed ' . $removedAssistantMessagesCounter . ' assistant messages');
        }

        if ($optimisedCounter > 0) {
            Log::info('Optimised ' . $optimisedCounter . ' old tool calls');
        }

        return $result;
    }

    private function removeForgetFunctionCalls(AssistantMessage $message, array $wrongFunctionCalls): ?AssistantMessage
    {
        $functionCalls = $message->toolCallsArray;
        $resultCalls = [];

        foreach ($functionCalls as $functionCall) {
            if (in_array($functionCall['id'], $wrongFunctionCalls)) {
                continue;
            }

            $resultCalls[] = $functionCall;
        }

        if (empty($resultCalls) && empty($message->content)) {
            return null;
        }

        return new AssistantMessage(
            $message->content,
            $resultCalls
        );
    }

    private function getFilePath(ToolMessage $message): string
    {
        $args = json_decode($message->args, true);

        return $args['url'] . ':' . $args['path'];
    }
}