<?php

namespace Anymodule\Agentmodule\Services\LLMGenerator;

use Vasenin26\Conversation\Chat;
use Anymodule\Agentmodule\Entity\LLMResult;
use Anymodule\Agentmodule\Interface\GPTProcessorInterface;
use Anymodule\Agentmodule\Interface\Tools\LLMTools;
use Anymodule\Agentmodule\Utils\Log;
use OpenAI;

class LMStudioClient implements GPTProcessorInterface
{
    public function __construct(
        private string        $apiKey,
        private LLMTools      $tools,
        private MessageMapper $messageMapper,
    )
    {
    }

    public function process(Chat $chat, bool $resultRequired = false): LLMResult
    {
        $client = OpenAI::factory()
            ->withApiKey($this->apiKey)
            ->withHttpClient(new \GuzzleHttp\Client(['timeout' => 0]))
            ->make();

        $promptTokens = 0;
        $completionTokens = 0;
        $totalTokens = 0;

        Log::info("Available LLM tools", array_map(fn($i) => $i['function']['name'], $this->tools->getMeta()));

        $answer = null;
        $finished = false;

        do {
            Log::info("LLM ask");

            try {
                $messages = $this->messageMapper->mapChat($chat);

                if (empty($messages)) {
                    return new LLMResult(
                        'Empty chat',
                        $chat->serialize(),
                        $promptTokens,
                        $completionTokens,
                        $totalTokens
                    );
                }

                $result = $client->chat()->create([
                    'model' => 'gpt-5-mini',
//                    'model' => 'gpt-4.1-nano',
                    'messages' => $messages,
                    'tools' => $this->tools->getMeta()
                ]);
            } catch (\Throwable $exception) {
                throw $exception;
            }

            $lastMessage = $result->choices[0]->message;
            $toolCalls = $lastMessage->toolCalls;

            $chat->addMessage($this->messageMapper->prepareAssistantMessage($lastMessage));

            Log::info("LLM Say:" . $lastMessage->content);

            if (empty($toolCalls)) {
                if ($resultRequired) {
                    $chat->addMessage($this->messageMapper->mapToUserMessage('Store answer with tools for finish'));
                } else {
                    $finished = true;
                }
            } else {
                foreach ($toolCalls as $toolCall) {

                    Log::info("LLM call tool " . $toolCall->function->name);

                    $toolResult = $this->tools->callTool($toolCall->function->name, $toolCall->function->arguments);

                    Log::info("Tool OK");

                    if (is_null($toolResult)) {

                        Log::info("Tool Failed");

                        $chat->addMessage($this->messageMapper->mapToToolMessage(
                            $toolCall->id,
                            $toolCall->function->name,
                            'Tools was call with wrong parameters',
                        ));

                        continue;
                    }

                    Log::info("Tool OK");

                    if ($this->tools->isResultFunction($toolCall->function->name)) {
                        $answer = $toolResult;
                        $finished = true;
                        $toolResult = 'Данные сохранены.';
                    }

                    $chat->addMessage($this->messageMapper->mapToToolMessage(
                        $toolCall->id,
                        $toolCall->function->name,
                        $toolResult,
                    ));
                }
            }

            if (!is_null($result->usage)) {
                $promptTokens += $result->usage->promptTokens ?? 0;
                $completionTokens += $result->usage->completionTokens ?? 0;
                $totalTokens += $result->usage->totalTokens ?? 0;
            }

        } while (!$finished);

        return new LLMResult(
            $answer,
            $chat->serialize(),
            $promptTokens,
            $completionTokens,
            $totalTokens
        );
    }
}
