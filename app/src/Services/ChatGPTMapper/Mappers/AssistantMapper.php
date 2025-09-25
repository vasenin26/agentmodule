<?php

namespace Anymodule\Agentmodule\Services\ChatGPTMapper\Mappers;

use Anymodule\Agentmodule\Services\ChatGPTMapper\Interface\MessageMapperInterface;
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
        if($message instanceof AssistantMessage) {
            $result = [
                'role' => 'assistant',
                'content' => $message->content,
            ];

            if(!empty($message->toolCallsArray)) {
                $normalizedToolCalls = [];

                foreach($message->toolCallsArray as $toolCall) {
                    $normalized = $this->normalizeToolCall($toolCall);
                    if ($normalized !== null) {
                        $normalizedToolCalls[] = $normalized;
                    }
                }

                if(!empty($normalizedToolCalls)) {
                    $result['tool_calls'] = $normalizedToolCalls;
                }
            }

            return $result;
        }

        throw new \Exception("Unsupported message type");
    }

    private function validateToolCallsArray(mixed $toolCall): bool
    {
        // Проверяем, что $toolCall - это массив с нужными ключами и структурой
        if (!is_array($toolCall)) {
            return false;
        }

        if (!isset($toolCall['id'], $toolCall['type'], $toolCall['function'])) {
            return false;
        }

        if ($toolCall['type'] !== 'function') {
            return false;
        }

        if (!is_array($toolCall['function'])) {
            return false;
        }

        if (!isset($toolCall['function']['name'], $toolCall['function']['arguments'])) {
            return false;
        }

        // Можно добавить дополнительные проверки на типы, если нужно
        if (!is_string($toolCall['id']) || !is_string($toolCall['function']['name']) || !is_string($toolCall['function']['arguments'])) {
            return false;
        }

        return true;
    }

    private function normalizeToolCall(mixed $toolCall): ?array
    {
        // Если это уже корректная структура OpenAI tool_calls - валидируем и возвращаем как есть
        if (is_array($toolCall)) {
            return $this->validateToolCallsArray($toolCall) ? $toolCall : null;
        }

        // Ожидаем объект со свойствами id, name, arguments (как возвращает процессор)
        if (is_object($toolCall)) {
            $id = property_exists($toolCall, 'id') ? $toolCall->id : null;
            $name = property_exists($toolCall, 'name') ? $toolCall->name : null;
            $arguments = property_exists($toolCall, 'arguments') ? $toolCall->arguments : null;

            if (!is_string($id) || !is_string($name)) {
                return null;
            }

            // Аргументы должны быть строкой JSON по требованиям OpenAI
            if (is_string($arguments)) {
                $argumentsJson = $arguments;
            } else {
                $encoded = json_encode($arguments, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                if ($encoded === false) {
                    return null;
                }
                $argumentsJson = $encoded;
            }

            return [
                'id' => $id,
                'type' => 'function',
                'function' => [
                    'name' => $name,
                    'arguments' => $argumentsJson,
                ],
            ];
        }

        return null;
    }
}