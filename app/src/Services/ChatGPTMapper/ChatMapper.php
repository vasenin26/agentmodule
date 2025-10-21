<?php

namespace Anymodule\Agentmodule\Services\ChatGPTMapper;

use Anymodule\Agentmodule\Interface\Git\GitRepoProviderInterface;
use Anymodule\Agentmodule\Interface\Tools\ToolsProviderInterface;
use Anymodule\Agentmodule\Interface\Url\UrlParserInterface;
use Anymodule\Agentmodule\Services\ChatGPTMapper\Interface\OpenAIMessageProcessorInterface;
use Anymodule\Agentmodule\Services\ChatGPTMapper\Mappers\AssistantMapper;
use Anymodule\Agentmodule\Services\ChatGPTMapper\Mappers\CallToolMapper;
use Anymodule\Agentmodule\Services\ChatGPTMapper\Mappers\DisappearingMessageMapper;
use Anymodule\Agentmodule\Services\ChatGPTMapper\Mappers\UserTaskMapper;
use Anymodule\Agentmodule\Services\ChatGPTMapper\Mappers\GitFileMapper;
use Anymodule\Agentmodule\Services\ChatGPTMapper\Mappers\SystemMapper;
use Anymodule\Agentmodule\Services\ChatGPTMapper\Mappers\ToolMapper;
use Anymodule\Agentmodule\Services\ChatGPTMapper\Mappers\UserMapper;
use Anymodule\Agentmodule\Services\OpenAIChat\DTO\OpenAiResult;
use Anymodule\Agentmodule\Services\OpenAIChat\Interface\MessageMapper;
use OpenAI\Responses\Chat\CreateResponse;
use Vasenin26\Conversation\Interface\Conversation;
use Vasenin26\Conversation\Messages\AssistantMessage;
use Vasenin26\Conversation\Messages\ToolMessage;

class ChatMapper implements MessageMapper
{
    private $mappers = [];

    public function __construct(
        private OpenAIMessageProcessorInterface $AIMessageProcessor,
        GitRepoProviderInterface $repositoryProvider,
        ToolsProviderInterface   $toolsService = null,
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

        if ($toolsService !== null) {
            $this->mappers[] = new CallToolMapper($toolsService);
        }
    }

    public function mapChat(Conversation $chat): array
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
}