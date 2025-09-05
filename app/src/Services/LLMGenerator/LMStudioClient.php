<?php

namespace Anymodule\Agentmodule\Services\LLMGenerator;

use Anymodule\Agentmodule\Entity\LLMResult;
use Anymodule\Agentmodule\Interface\GPTProcessorInterface;
use Anymodule\Agentmodule\Interface\Tools\LLMTools;
use Anymodule\Agentmodule\Utils\Log;
use OpenAI;

class LMStudioClient implements GPTProcessorInterface
{

    public function __construct(
        private string   $apiKey,
        private LLMTools $tools
    )
    {
    }

    public function process(array $messages): LLMResult
    {
        $client = OpenAI::factory()
            ->withApiKey($this->apiKey)
            ->withHttpClient(new \GuzzleHttp\Client(['timeout' => 0]))
            ->make();

        $promptTokens = 0;
        $completionTokens = 0;
        $totalTokens = 0;

        Log::info("Available LLM tools", array_map(fn($i) => $i['function']['name'], $this->tools->getMeta()));

        do {
            $answer = null;

            Log::info("LLM ask");

            try {
                $result = $client->chat()->create([
                    'model' => 'gpt-5-mini',
//                    'model' => 'gpt-4.1-nano',
                    'messages' => $messages,
                    'tools' => $this->tools->getMeta()
                ]);
            } catch (\Throwable $exception) {
                var_dump($messages);
                var_dump($exception);

                throw $exception;
            }

            $lastMessage = $result->choices[0]->message;
            $toolCalls = $lastMessage->toolCalls;

            $messages[] = $this->prepareMessage($lastMessage);

            Log::info("LLM Say:" . $lastMessage->content);

            if (empty($toolCalls)) {
                $messages[] = ['role' => 'user', 'content' => 'Store answer with tools for finish'];
            } else {
                foreach ($toolCalls as $toolCall) {

                    Log::info("LLM call tool " . $toolCall->function->name);

                    $toolResult = $this->tools->callTool($toolCall->function->name, $toolCall->function->arguments);

                    Log::info("Tool OK");

                    if (is_null($toolResult)) {

                        Log::info("Tool Failed");

                        $messages[] = [
                            'role' => 'tool',
                            'tool_call_id' => $toolCall->id,
                            'content' => 'Tools was call with wrong parameters'
                        ];

                        continue;
                    }

                    Log::info("Tool OK");

                    if ($this->tools->isResultFunction($toolCall->function->name)) {
                        $answer = $toolResult;
                        $toolResult = 'Данные сохранены.';
                    }

                    $messages[] = [
                        'role' => 'tool',
                        'tool_call_id' => $toolCall->id,
                        'content' => $toolResult
                    ];
                }
            }

            if (!is_null($result->usage)) {
                $promptTokens += $result->usage->promptTokens ?? 0;
                $completionTokens += $result->usage->completionTokens ?? 0;
                $totalTokens += $result->usage->totalTokens ?? 0;
            }

        } while (is_null($answer));

        return new LLMResult(
            $answer,
            $messages,
            $promptTokens,
            $completionTokens,
            $totalTokens
        );
    }

    private function prepareMessage(OpenAI\Responses\Chat\CreateResponseMessage $lastMessage): array
    {
        $toolCalls = $lastMessage->toolCalls;

        $toolCallsArray = [];
        foreach ($toolCalls as $tc) {
            $toolCallsArray[] = [
                'id' => $tc->id,
                'type' => 'function',
                'function' => [
                    'name' => $tc->function->name,
                    'arguments' => $tc->function->arguments,
                ],
            ];
        }

        return [
            'role' => $lastMessage->role,
            'content' => $lastMessage->content,
            'tool_calls' => $toolCallsArray ?: null
        ];
    }
}
