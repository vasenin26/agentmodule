<?php

namespace Anymodule\Agentmodule\Services\LLMGenerator;

use Vasenin26\Conversation\Chat;
use Vasenin26\Conversation\Message;
use OpenAI\Responses\Chat\CreateResponseMessage;

interface MessageMapper
{
    public function mapChat(Chat $chat): array;

    public function prepareAssistantMessage(CreateResponseMessage $message): Message;

    public function mapToUserMessage(string $string): Message;

    public function mapToToolMessage(
        bool $success,
        string $id,
        string $toolName,
        string $props,
        string $result
    ): Message;

    public function mapToHelpInstructionMessage(string $content): Message;
}