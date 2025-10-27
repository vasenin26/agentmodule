<?php

namespace Anymodule\Agentmodule\Application\Mappers\ChatGPTMapper;

use Anymodule\Agentmodule\Application\Mappers\ChatGPTMapper\Container\TaskList;
use Anymodule\Agentmodule\Application\Mappers\ChatGPTMapper\Interface\OpenAIMessageMapperInterface;
use Anymodule\Agentmodule\Entity\ContextConversation;
use Anymodule\Agentmodule\Services\OpenAIChat\DTO\OpenAiResult;
use Anymodule\Agentmodule\Services\OpenAIChat\Interface\ContextMapper;
use Anymodule\Agentmodule\Services\OpenAIChat\Interface\MessageMapper;
use Anymodule\Agentmodule\Utils\Log;

class ChatContextMapper implements ContextMapper
{

    public function __construct(
        private OpenAIMessageMapperInterface $messageProcessor,
        private MessageMapper                $messageMapper,
    )
    {
    }

    public function mapConversation(ContextConversation $contextConversation): array
    {
        $messages = $this->messageMapper->mapChat($contextConversation->conversation);

        if (!empty($contextConversation->context->tasks)) {
            $taskListContainer = new TaskList($contextConversation->context->tasks);
            $lastUserMessageIndex = $this->findLastUserMessageIndex($messages);
            $messages = $this->insertBefore($lastUserMessageIndex, $messages, $taskListContainer);
        }

        Log::storeMessages($messages);

        return $messages;
    }

    public function prepareAssistantMessage(\OpenAI\Responses\Chat\CreateResponse $result): OpenAiResult
    {
        return $this->messageProcessor->prepareAssistantMessage($result);
    }

    private function findLastUserMessageIndex(array $messages): int
    {
        $lastIndex = -1;
        foreach ($messages as $index => $message) {
            if (isset($message['role']) && $message['role'] === 'user') {
                $lastIndex = $index;
            }
        }
        return $lastIndex;
    }

    private function insertBefore(int $lastUserMessageIndex, array $messages, TaskList $taskListContainer): array
    {
        $taskMessage = $taskListContainer->getMessage();
        if ($lastUserMessageIndex < 0) {
            // Вставляем в начало, если индекс не найден
            array_unshift($messages, $taskMessage);
        } else {
            array_splice($messages, $lastUserMessageIndex, 0, [$taskMessage]);
        }
        return $messages;
    }

}