<?php

namespace Anymodule\Agentmodule\Services\ChatGPTMapper;

use Anymodule\Agentmodule\Interface\Git\GitRepoProviderInterface;
use Anymodule\Agentmodule\Services\ChatGPTMapper\Mappers\ToolMapper;
use Vasenin26\Conversation\Chat;
use Vasenin26\Conversation\Message;
use Vasenin26\Conversation\Messages\AssistantMessage;
use Vasenin26\Conversation\Messages\DisappearingMessage;
use Vasenin26\Conversation\Messages\InfoMessage;
use Vasenin26\Conversation\Messages\SystemMessage;
use Vasenin26\Conversation\Messages\ToolMessage;
use Vasenin26\Conversation\Messages\UserMessage;
use Anymodule\Agentmodule\Services\ChatGPTMapper\Mappers\AssistantMapper;
use Anymodule\Agentmodule\Services\ChatGPTMapper\Mappers\GitFileMapper;
use Anymodule\Agentmodule\Services\ChatGPTMapper\Mappers\SystemMapper;
use Anymodule\Agentmodule\Services\ChatGPTMapper\Mappers\UserMapper;
use Anymodule\Agentmodule\Interface\Url\UrlParserInterface;
use Anymodule\Agentmodule\Utils\ExtractRepoUrl;
use Anymodule\Agentmodule\Services\LLMGenerator\MessageMapper;
use OpenAI\Responses\Chat\CreateResponseMessage;

class ChatMapper implements MessageMapper
{
    private $mappers = [];

    public function __construct(
        GitRepoProviderInterface $repositoryProvider,
        UrlParserInterface       $urlParser = null
    )
    {
        $urlParser = $urlParser ?? new ExtractRepoUrl();

        $this->mappers = [
            new UserMapper(),
            new AssistantMapper(),
            new SystemMapper(),
            new ToolMapper(),
            new GitFileMapper($repositoryProvider, $urlParser),
        ];
    }

    public function mapChat(Chat $chat): array
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


    public function prepareAssistantMessage(CreateResponseMessage $message): Message
    {
        $toolCalls = $message->toolCalls;

        $toolCallsArray = [];
        foreach ($toolCalls as $tc) {
            $toolCallsArray[] = [
                'id' => $tc->id,
                'type' => 'function',
                'function' => [
                    'name' => $tc->function->name,
                    'arguments' => $tc->function->arguments,
                ],
            ];
        }

        return new AssistantMessage(
            content: $message->content ?? '',
            toolCallsArray: $toolCallsArray ?: []
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