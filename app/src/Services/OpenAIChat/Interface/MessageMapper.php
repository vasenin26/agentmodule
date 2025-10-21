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
}