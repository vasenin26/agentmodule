<?php

namespace Anymodule\Agentmodule\Services\OpenAIChat\Mapper;

use Anymodule\Agentmodule\Application\Mappers\ChatGPTMapper\Interface\OpenAIMessageMapperInterface;
use Anymodule\Agentmodule\Services\OpenAIChat\DTO\OpenAiResult;

class OpenAiResultMapper implements OpenAIMessageMapperInterface
{
    public function prepareAssistantMessage(\OpenAI\Responses\Chat\CreateResponse $result): OpenAiResult
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
}