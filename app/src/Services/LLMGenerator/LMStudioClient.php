<?php

namespace Anymodule\Agentmodule\Services\LLMGenerator;

use Anymodule\Agentmodule\Entity\LLMResult;
use Anymodule\Agentmodule\Interface\ToolServiceFactoryInterface;
use Illuminate\Support\Facades\Log;
use OpenAI;

class LMStudioClient
{

    public function __construct(
        private ToolServiceFactoryInterface $toolsFactory
    )
    {
    }

    public function processMessages(array $messages): LLMResult
    {
//        $client = OpenAI::factory()
//            ->withApiKey('sk-proj-1234567890')
//            ->withBaseUri('http://host.docker.internal:1234/v1')
//            ->make();
        $client = OpenAI::factory()
            ->withApiKey(env('OPENAI_API_KEY'))
            ->make();

        $tools = $this->toolsFactory->withAllTools();

        // Извлекаем детализированную информацию о токенах
        $promptTokens = 0;
        $completionTokens = 0;
        $totalTokens = 0;

        do {
            $answer = null;

            Log::info('Ask LLM', ['message' => count($messages)]);

            try {
                $result = $client->chat()->create([
                    'model' => 'gpt-5-mini',
                    'messages' => $messages,
                    'tools' => $tools->getMeta()
                ]);
            } catch (\Throwable $exception) {
                var_dump($messages);
                var_dump($exception);
                Log::error($exception->getMessage());
                throw $exception;
            }

            Log::info('LLM OK', ['message' => count($messages)]);

            $lastMessage = $result->choices[0]->message;
            $toolCalls = $lastMessage->toolCalls;

            $messages[] = $this->prepareMessage($lastMessage);

            if (empty($toolCalls)) {
                $messages[] = ['role' => 'user', 'content' => 'Store answer with tools for finish'];
            } else {
                foreach ($toolCalls as $toolCall) {

                    Log::info("LLM call tool: " . $toolCall->function->name . " with args " . $toolCall->function->arguments);

                    $toolResult = $tools->callTool($toolCall->function->name, $toolCall->function->arguments);

                    Log::info("Tool finished", ['result' => $toolResult]);

                    if (is_null($toolResult)) {
                        $messages[] = [
                            'role' => 'tool',
                            'tool_call_id' => $toolCall->id,
                            'content' => 'Tools was call with wrong parameters'
                        ];

                        continue;
                    }

                    $messages[] = [
                        'role' => 'tool',
                        'tool_call_id' => $toolCall->id,
                        'content' => $toolResult
                    ];

                    if ($tools->isResultFunction($toolCall->function->name)) {
                        $answer = $toolResult;
                    }
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
