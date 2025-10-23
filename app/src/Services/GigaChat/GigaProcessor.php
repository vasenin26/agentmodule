<?php

namespace Anymodule\Agentmodule\Services\GigaChat;

use Anymodule\Agentmodule\Application\ChatAgent\Interface\ChatProcessorInterface;
use Anymodule\Agentmodule\Application\ChatAgent\Interface\ChatResultInterface;
use Anymodule\Agentmodule\Entity\ModelMeta;
use Anymodule\Agentmodule\Interface\Tools\ToolsProviderInterface;
use Anymodule\Agentmodule\Services\GigaChat\DTO\GigaResult;
use Anymodule\Agentmodule\Services\GigaChat\Interface\GigaChatMapperInterface;
use Anymodule\Agentmodule\Services\GigaChat\Interface\GigaClientInterface;
use Anymodule\Agentmodule\Services\OpenAIChat\Exception\ContextOverloadException;
use Anymodule\Agentmodule\Utils\Log;
use Vasenin26\Conversation\Interface\Conversation;

class GigaProcessor implements ChatProcessorInterface
{
    public function __construct(
        private ModelMeta $modelMeta,
        private GigaChatMapperInterface $mapper,
        private GigaClientInterface $client,
    )
    {
    }

    public function contextSize(): int
    {
        return 0;
    }

    public function getModelMeta(): ModelMeta
    {
        return $this->modelMeta;
    }

    public function process(Conversation $chat, ?ToolsProviderInterface $tools): ChatResultInterface
    {
        $messages = $this->mapper->mapChat($chat);

        if (empty($messages)) {
            Log::warning('Empty conversation found');
            return GigaResult::empty();
        }

        try {
            Log::info('Ask GIGA');

            $result = $this->client->process($this->modelMeta, $tools, $messages);
        } catch (\Throwable $exception) {
            Log::exception($exception, 'GIGA Chat API error', [
                'model' => $this->modelMeta,
                'messages_count' => count($messages ?? []),
                'tools_count' => count($tools?->getMeta() ?? []),
            ]);

            return GigaResult::error($exception->getMessage());
        }

        Log::info('GIGA OK');

        $result = $this->mapper->prepareAssistantMessage($result);
        $usage = $result->getTokenUsage();

        if ($usage->total > $this->contextSize()) {
            throw new ContextOverloadException();
        }

        return $result;
    }
}