<?php

namespace Anymodule\Agentmodule\Services\OpenAIChat\Interface;

use Anymodule\Agentmodule\Services\OpenAIChat\DTO\OpenAiResult;
use OpenAI\Responses\Chat\CreateResponse;
use Vasenin26\Conversation\Interface\Conversation;
use Vasenin26\Conversation\Message;

interface MessageMapper
{
    public function mapChat(Conversation $chat): array;

    public function prepareAssistantMessage(CreateResponse $result): OpenAiResult;

    public function mapToUserMessage(string $string): Message;

    public function mapToToolMessage(
        bool $success,
        string $id,
        string $toolName,
        string $props,
        string $result
    ): Message;

    public function mapToHelpInstructionMessage(string $content): Message;

    public function mapToInfoMessage(string $content): Message;
}