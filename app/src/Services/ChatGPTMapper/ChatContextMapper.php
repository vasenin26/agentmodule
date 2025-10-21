<?php

namespace Anymodule\Agentmodule\Services\ChatGPTMapper;

use Anymodule\Agentmodule\Entity\ContextConversation;
use Anymodule\Agentmodule\Services\ChatGPTMapper\Container\TaskList;
use Anymodule\Agentmodule\Services\ChatGPTMapper\Interface\OpenAIMessageProcessorInterface;
use Anymodule\Agentmodule\Services\OpenAIChat\DTO\OpenAiResult;
use Anymodule\Agentmodule\Services\OpenAIChat\Interface\MessageMapper;
use Anymodule\Agentmodule\Services\OpenAIChat\Interface\ContextMapper;

class ChatContextMapper implements ContextMapper
{

    public function __construct(
        private OpenAIMessageProcessorInterface $messageProcessor,
        private MessageMapper                   $messageMapper,
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
            if (isset($message['type']) && $message['type'] === 'user') {
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