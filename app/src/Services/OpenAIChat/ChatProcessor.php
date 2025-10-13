<?php

namespace Anymodule\Agentmodule\Services\OpenAIChat;

use Anymodule\Agentmodule\Entity\ModelMeta;
use Anymodule\Agentmodule\Interface\Tools\ToolsProviderInterface;
use Anymodule\Agentmodule\Services\ChatAgent\Interface\ChatProcessorInterface;
use Anymodule\Agentmodule\Services\ChatAgent\Interface\ChatResultInterface;
use Anymodule\Agentmodule\Services\OpenAIChat\DTO\OpenAiResult;
use Anymodule\Agentmodule\Services\OpenAIChat\Exception\ContextOverloadException;
use Anymodule\Agentmodule\Services\OpenAIChat\Interface\MessageMapper;
use Anymodule\Agentmodule\Utils\Log;
use OpenAI\Client;
use Vasenin26\Conversation\Interface\Conversation;

class ChatProcessor implements ChatProcessorInterface
{
    public function __construct(
        private Client        $apiClient,
        private ModelMeta     $model,
        private MessageMapper $messageMapper,
    )
    {
    }

    public function process(Conversation $chat, ToolsProviderInterface $tools): ChatResultInterface
    {
        $messages = $this->messageMapper->mapChat($chat);

        if (empty($messages)) {
            Log::warning('Empty conversation found');
            return OpenAiResult::empty();
        }

        try {
            Log::info('Ask LLM');

            $result = $this->apiClient->chat()->create([
                'model' => $this->model->name,
                'messages' => $messages,
                'tools' => $tools->getMeta()
            ]);
        } catch (\Throwable $exception) {
//            Log::storeMessages($messages);
            Log::exception($exception, 'OpenAI Chat API error', [
                'model' => $this->model,
                'messages_count' => count($messages ?? []),
                'tools_count' => count($tools->getMeta()),
            ]);

            return OpenAiResult::error($exception->getMessage());
        }

        Log::info('LLM OK');

        $result = $this->messageMapper->prepareAssistantMessage($result);
        $usage = $result->getTokenUsage();

        if ($usage->total > $this->contextSize()) {
            throw new ContextOverloadException();
        }

        return $result;
    }

    public function contextSize(): int
    {
        return $this->model->contextSize;
    }
}