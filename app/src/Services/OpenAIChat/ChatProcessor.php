<?php

namespace Anymodule\Agentmodule\Services\OpenAIChat;

use Anymodule\Agentmodule\Interface\Tools\ToolsProvider;
use Anymodule\Agentmodule\Services\ChatAgent\Interface\CharProcessorInterface;
use Anymodule\Agentmodule\Services\ChatAgent\Interface\ChatResultInterface;
use Anymodule\Agentmodule\Services\OpenAIChat\DTO\OpenAiResult;
use Anymodule\Agentmodule\Services\OpenAIChat\Interface\MessageMapper;
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
            $logDir = '/app/logs';

            if (!is_dir($logDir)) {
                @mkdir($logDir, 0777, true);
            }
            $filename = $logDir . '/chat_' . date('Ymd_His') . '_' . uniqid() . '.json';
            @file_put_contents($filename, json_encode($messages, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

            return OpenAiResult::error($exception->getMessage());
        }
    }
}