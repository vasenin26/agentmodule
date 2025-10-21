<?php

namespace Anymodule\Agentmodule\Services\OpenAIChat\Interface;

use Anymodule\Agentmodule\Services\OpenAIChat\DTO\OpenAiResult;

interface ContextMapper
{
    public function mapConversation(\Anymodule\Agentmodule\Entity\ContextConversation $contextConversation): array;
    public function prepareAssistantMessage(\OpenAI\Responses\Chat\CreateResponse $result): OpenAiResult;
}