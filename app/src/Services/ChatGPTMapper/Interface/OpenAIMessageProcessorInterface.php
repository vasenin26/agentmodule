<?php

namespace Anymodule\Agentmodule\Services\ChatGPTMapper\Interface;

use Anymodule\Agentmodule\Services\OpenAIChat\DTO\OpenAiResult;

interface OpenAIMessageProcessorInterface
{

    public function prepareAssistantMessage(\OpenAI\Responses\Chat\CreateResponse $result): OpenAiResult;
}