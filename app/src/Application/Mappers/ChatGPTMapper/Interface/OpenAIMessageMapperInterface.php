<?php

namespace Anymodule\Agentmodule\Application\Mappers\ChatGPTMapper\Interface;

use Anymodule\Agentmodule\Services\OpenAIChat\DTO\OpenAiResult;

interface OpenAIMessageMapperInterface
{

    public function prepareAssistantMessage(\OpenAI\Responses\Chat\CreateResponse $result): OpenAiResult;
}