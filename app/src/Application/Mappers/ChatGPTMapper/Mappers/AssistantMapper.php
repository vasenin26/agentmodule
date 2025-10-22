<?php

namespace Anymodule\Agentmodule\Application\Mappers\ChatGPTMapper\Mappers;

use Anymodule\Agentmodule\Application\Mappers\ChatGPTMapper\Interface\MessageMapperInterface;
use Anymodule\Agentmodule\Utils\Log;
use Vasenin26\Conversation\Message;
use Vasenin26\Conversation\Messages\AssistantMessage;

class AssistantMapper implements MessageMapperInterface
{

    public function supports(Message $message): bool
    {
        return $message instanceof AssistantMessage;
    }

    public function map(Message $message): array
    {
        if ($message instanceof AssistantMessage) {
            $result = [
                'role' => 'assistant',
                'content' => $message->content,
            ];

            if (!empty($message->toolCallsArray)) {
                $normalizedToolCalls = [];

                foreach ($message->toolCallsArray as $toolCall) {
                    $normalized = $this->normalizeToolCall($toolCall);
                    if ($normalized !== null) {
                        $normalizedToolCalls[] = $normalized;
                    }
                }

                if (!empty($normalizedToolCalls)) {
                    $result['tool_calls'] = $normalizedToolCalls;
                }
            }

            return $result;
        }

        throw new \Exception("Unsupported message type");
    }

    private function validateToolCallsArray(mixed $toolCall): bool
    {
        if (!is_array($toolCall)) {
            return false;
        }

        if (!isset($toolCall['id'], $toolCall['name'], $toolCall['arguments'])) {
            return false;
        }

        if (!is_string($toolCall['id']) || !is_string($toolCall['name']) || !is_string($toolCall['arguments'])) {
            return false;
        }

        return true;
    }

    private function normalizeToolCall(mixed $toolCall): ?array
    {
        if ($this->validateToolCallsArray($toolCall)) {
            return [
                'id' => $toolCall['id'],
                'type' => 'function',
                'function' => [
                    'name' => $toolCall['name'],
                    'arguments' => $toolCall['arguments'],
                ]
            ];
        } else {
            Log::warning("Tool call '{$toolCall['name']}' is not valid", $toolCall);
        }

        return null;
    }
}