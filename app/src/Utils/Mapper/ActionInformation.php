<?php

namespace Anymodule\Agentmodule\Utils\Mapper;

use Anymodule\Agentmodule\Entity\ProcessingResult;
use Vasenin26\Conversation\Messages\AssistantMessage;
use Vasenin26\Conversation\Messages\ToolMessage;

class ActionInformation
{
    private array $counters = [];

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

                    if(!array_key_exists($lastMessage->name, $this->counters)) {
                        $this->counters[$lastMessage->name] = 0;
                    }

                    $this->counters[$lastMessage->name]++;
                }
            }
        }

        return new ProcessingResult(
            completed: false,
            answer: $answer,
            conversation: $processingResult->conversation,
            contextFill: 0,
            promptTokens: $processingResult->promptTokens,
            completionTokens: $processingResult->completionTokens,
            totalTokens: $processingResult->totalTokens,
            payload: $this->counters,
        );
    }
}