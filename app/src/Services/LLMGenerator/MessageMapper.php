<?php

namespace Anymodule\Agentmodule\Services\LLMGenerator;

use Anymodule\Agentmodule\Entity\Conversation\Chat;
use Anymodule\Agentmodule\Entity\Conversation\Message;
use OpenAI\Responses\Chat\CreateResponseMessage;

interface MessageMapper
{
    public function mapChat(Chat $chat): array;

    public function prepareAssistantMessage(CreateResponseMessage $message): Message;

    public function mapToUserMessage(string $string): Message;

    public function mapToToolMessage(string $id, string $toolName, string $result): Message;
}