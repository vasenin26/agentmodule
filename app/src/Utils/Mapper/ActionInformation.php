<?php

namespace Anymodule\Agentmodule\Utils\Mapper;

use Anymodule\Agentmodule\Entity\ProcessingResult;
use Vasenin26\Conversation\Messages\AssistantMessage;
use Vasenin26\Conversation\Messages\ToolMessage;

class ActionInformation
{
    public function fromResult(ProcessingResult $processingResult): ProcessingResult
    {
        $answer = $processingResult->answer;

        if (empty($answer)) {
            $messages = $processingResult->conversation->getMessages();
            $lastMessage = end($messages);

            if ($lastMessage) {
                if ($lastMessage instanceof AssistantMessage) {
                    $answer = $lastMessage->content;
                    if (empty($answer)) {
                        $answer = 'Call tool: ' . join(', ', array_map(fn($t) => $t->name ?? 'unknown', $lastMessage->toolCallsArray));
                    }
                } elseif ($lastMessage instanceof ToolMessage) {
                    $answer = 'Processing tool result: ' . $lastMessage->name;
                }
            }
        }

        return new ProcessingResult(
            completed: false,
            answer: $answer,
            conversation: $processingResult->conversation,
            promptTokens: $processingResult->promptTokens,
            completionTokens: $processingResult->completionTokens,
            totalTokens: $processingResult->totalTokens
        );
    }
}