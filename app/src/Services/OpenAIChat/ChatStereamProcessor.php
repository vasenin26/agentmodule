<?php

namespace Anymodule\Agentmodule\Services\OpenAIChat;

use Anymodule\Agentmodule\Interface\Tools\ToolsProviderInterface;
use Anymodule\Agentmodule\Services\ChatAgent\Interface\ChatProcessorInterface;
use Anymodule\Agentmodule\Services\ChatAgent\Interface\ChatResultInterface;
use Anymodule\Agentmodule\Services\OpenAIChat\DTO\OpenAiResult;
use Anymodule\Agentmodule\Services\OpenAIChat\Interface\MessageMapper;
use Anymodule\Agentmodule\Utils\Log;
use OpenAI;
use OpenAI\Responses\Chat\CreateResponse;
use Vasenin26\Conversation\Interface\Conversation;

class ChatStereamProcessor implements ChatProcessorInterface
{
    public function __construct(
        private string        $apiKey,
        private string        $model,
        private MessageMapper $messageMapper,
    )
    {
    }

    public function process(Conversation $chat, ToolsProviderInterface $tools): ChatResultInterface
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

            $stream = $client->chat()->createStreamed([
                'model' => $this->model,
                'messages' => $messages,
                'tools' => $tools->getMeta(),
                'stream' => true,
            ]);

            $fullMessage = '';
            $toolCalls = [];
            $sentTokens = 0;
            $receivedTokens = 0;

            foreach ($stream as $response) {
                $choice = $response->choices[0];

                // выводим дельту в stdout
                if (isset($choice->delta->content)) {
                    echo $choice->delta->content;
                    flush();
                    $fullMessage .= $choice->delta->content;
                }

                // если модель вызвала инструмент
                if (isset($choice->delta->tool_call)) {
                    $toolCalls[] = [
                        'id' => $choice->delta->tool_call->id ?? null,
                        'name' => $choice->delta->tool_call->name ?? '',
                        'arguments' => $choice->delta->tool_call->arguments ?? [],
                    ];
                }

                // можно накапливать токены, если доступны
                $sentTokens += $response->usage->prompt_tokens ?? 0;
                $receivedTokens += $response->usage->completion_tokens ?? 0;
            }

            $totalTokens = $sentTokens + $receivedTokens;

            return new OpenAiResult(
                $fullMessage,
                $toolCalls,
                $sentTokens,
                $receivedTokens,
                $totalTokens
            );

        } catch (\Throwable $exception) {
            Log::exception($exception, 'OpenAI streaming error');
            return OpenAiResult::error($exception->getMessage());
        }
    }


    public function contextSize(): int
    {
        return 700_000;
    }
}