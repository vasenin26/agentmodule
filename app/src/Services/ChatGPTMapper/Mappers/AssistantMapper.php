<?php

namespace Anymodule\Agentmodule\Services\ChatGPTMapper\Mappers;

use Vasenin26\Conversation\Message;
use Vasenin26\Conversation\Messages\AssistantMessage;
use Anymodule\Agentmodule\Services\ChatGPTMapper\Interface\MessageMapperInterface;

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
                $toolCallsArray = [];

                foreach($message->toolCallsArray as $toolCall) {
                    if($this->validateToolCallsArray($toolCall)) {
                        $toolCallsArray[] = $toolCall;
                    }
                }

                if(!empty($toolCallsArray)) {
                    $result['tool_calls'] = $toolCallsArray;
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
}