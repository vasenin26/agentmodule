<?php

namespace Anymodule\Agentmodule\Services\OpenAIChat;

use Anymodule\Agentmodule\Application\ChatAgent\Interface\ChatResultInterface;
use Anymodule\Agentmodule\Application\ChatAgent\Interface\ContextConversationProcessorInterface;
use Anymodule\Agentmodule\Entity\ContextConversation;
use Anymodule\Agentmodule\Entity\ModelMeta;
use Anymodule\Agentmodule\Interface\Tools\ToolsProviderInterface;
use Anymodule\Agentmodule\Services\OpenAIChat\DTO\OpenAiResult;
use Anymodule\Agentmodule\Services\OpenAIChat\Exception\ContextOverloadException;
use Anymodule\Agentmodule\Services\OpenAIChat\Interface\ContextMapper;
use Anymodule\Agentmodule\Utils\Log;
use OpenAI\Client;

readonly class ContextConversationProcessor implements ContextConversationProcessorInterface
{
    public function __construct(
        private Client        $apiClient,
        private ModelMeta     $model,
        private ContextMapper $messageMapper,
    )
    {
    }

    public function getModelMeta(): ModelMeta
    {
        return $this->model;
    }

    public function process(ContextConversation $contextConversation, ?ToolsProviderInterface $tools): ChatResultInterface
    {
        $messages = $this->messageMapper->mapConversation($contextConversation);

        Log::storeMessages($messages);

        if (empty($messages)) {
            Log::warning('Empty conversation found');
            return OpenAiResult::empty();
        }

        try {
            Log::info('Ask LLM');

            $result = $this->apiClient->chat()->create([
                'model' => $this->model->name,
                'messages' => $messages,
                'tools' => $tools?->getMeta() ?? []
            ]);
        } catch (\Throwable $exception) {
//            Log::storeMessages($messages);
            Log::exception($exception, 'OpenAI Chat API error', [
                'model' => $this->model,
                'messages_count' => count($messages ?? []),
                'tools_count' => count($tools?->getMeta() ?? []),
            ]);

            return OpenAiResult::error($exception->getMessage());
        }

        Log::info('LLM OK');

        $result = $this->messageMapper->prepareAssistantMessage($result);
        $usage = $result->getTokenUsage();

        $context = $this->contextSize();
        $modelName = $this->model->name;
        Log::info("Model: $modelName Usage: $usage->sent / $usage->received / $usage->total  Context: $context");

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