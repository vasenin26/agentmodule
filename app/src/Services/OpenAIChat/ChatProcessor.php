<?php

namespace Anymodule\Agentmodule\Services\OpenAIChat;

use Anymodule\Agentmodule\Interface\Tools\ToolsProvider;
use Anymodule\Agentmodule\Services\ChatAgent\Interface\CharProcessorInterface;
use Anymodule\Agentmodule\Services\ChatAgent\Interface\ChatResultInterface;
use Anymodule\Agentmodule\Services\OpenAIChat\DTO\OpenAiResult;
use Anymodule\Agentmodule\Services\OpenAIChat\Interface\MessageMapper;
use Anymodule\Agentmodule\Utils\Log;
use OpenAI;
use Vasenin26\Conversation\Chat;
use Vasenin26\Conversation\Interface\Conversation;

class ChatProcessor implements CharProcessorInterface
{
    public function __construct(
        private string        $apiKey,
        private string        $model,
        private MessageMapper $messageMapper,
    )
    {
    }

    public function process(Conversation $chat, ToolsProvider $tools): ChatResultInterface
    {
        $client = OpenAI::factory()
            ->withApiKey($this->apiKey)
            ->withHttpClient(new \GuzzleHttp\Client(['timeout' => 0]))
            ->make();

        $messages = null;

        try {
            $messages = $this->messageMapper->mapChat($chat);

            if (empty($messages)) {
                return OpenAiResult::empty();
            }

            $result = $client->chat()->create([
                'model' => $this->model,
                'messages' => $messages,
                'tools' => $tools->getMeta()
            ]);

            return $this->messageMapper->prepareAssistantMessage($result);
        } catch (\Throwable $exception) {
            Log::storeMessages($messages);
            return OpenAiResult::error($exception->getMessage());
        }
    }
}