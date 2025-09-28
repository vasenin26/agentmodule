<?php

namespace Anymodule\Agentmodule\Services\ChatGPTMapper;

use Anymodule\Agentmodule\Interface\Git\GitRepoProviderInterface;
use Anymodule\Agentmodule\Interface\Url\UrlParserInterface;
use Anymodule\Agentmodule\Services\ChatGPTMapper\Mappers\AssistantMapper;
use Anymodule\Agentmodule\Services\ChatGPTMapper\Mappers\CallToolMapper;
use Anymodule\Agentmodule\Services\ChatGPTMapper\Mappers\UserTaskMapper;
use Anymodule\Agentmodule\Services\ChatGPTMapper\Mappers\GitFileMapper;
use Anymodule\Agentmodule\Services\ChatGPTMapper\Mappers\SystemMapper;
use Anymodule\Agentmodule\Services\ChatGPTMapper\Mappers\ToolMapper;
use Anymodule\Agentmodule\Services\ChatGPTMapper\Mappers\UserMapper;
use Anymodule\Agentmodule\Services\OpenAIChat\DTO\OpenAiResult;
use Anymodule\Agentmodule\Services\OpenAIChat\Interface\MessageMapper;
use Anymodule\Agentmodule\Services\ToolsService\ToolsService;
use Anymodule\Agentmodule\Utils\ExtractRepoUrl;
use OpenAI\Responses\Chat\CreateResponse;
use Vasenin26\Conversation\Interface\Conversation;
use Vasenin26\Conversation\Message;
use Vasenin26\Conversation\Messages\DisappearingMessage;
use Vasenin26\Conversation\Messages\InfoMessage;
use Vasenin26\Conversation\Messages\ToolMessage;
use Vasenin26\Conversation\Messages\UserMessage;

class ChatMapper implements MessageMapper
{
    private $mappers = [];

    public function __construct(
        GitRepoProviderInterface $repositoryProvider,
        UrlParserInterface       $urlParser = null,
        ToolsService             $toolsService = null
    )
    {
        $urlParser = $urlParser ?? new ExtractRepoUrl();

        $this->mappers = [
            new UserMapper(),
            new UserTaskMapper(),
            new AssistantMapper(),
            new SystemMapper(),
            new ToolMapper(),
            new GitFileMapper($repositoryProvider, $urlParser),
        ];

        if ($toolsService !== null) {
            $this->mappers[] = new CallToolMapper($toolsService);
        }
    }

    public function mapChat(Conversation $chat): array
    {
        $messages = [];

        foreach ($chat->getMessages() as $message) {
            foreach ($this->mappers as $mapper) {
                if ($mapper->supports($message)) {
                    $messages[] = $mapper->map($message);
                    break;
                }
            }
        }

        return $messages;
    }


    public function prepareAssistantMessage(CreateResponse $result): OpenAiResult
    {
        $message = $result->choices[0]->message;
        $toolCalls = $message->toolCalls;

        $toolCallsArray = [];
        foreach ($toolCalls as $tc) {
            $toolCallsArray[] = [
                'id' => $tc->id,
                'name' => $tc->function->name,
                'arguments' => $tc->function->arguments,
            ];
        }

        $promptTokens = 0;
        $completionTokens = 0;
        $totalTokens = 0;

        if (!is_null($result->usage)) {
            $promptTokens += $result->usage->promptTokens ?? 0;
            $completionTokens += $result->usage->completionTokens ?? 0;
            $totalTokens += $result->usage->totalTokens ?? 0;
        }

        return new OpenAiResult(
            message: $message->content ?? '',
            toolCall: $toolCallsArray ?: [],
            sent: $promptTokens ?? 0,
            received: $completionTokens ?? 0,
            total: $totalTokens ?? 0,
        );
    }

    public function mapToUserMessage(string $string): Message
    {
        return new UserMessage($string);
    }

    public function mapToToolMessage(
        bool   $success,
        string $id,
        string $toolName,
        string $props,
        string $result): Message
    {
        return new ToolMessage($success, $id, $toolName, $props, $result);
    }

    public function mapToHelpInstructionMessage(string $content): Message
    {
        return new DisappearingMessage($content);
    }

    public function mapToInfoMessage(string $content): Message
    {
        return new InfoMessage($content);
    }
}