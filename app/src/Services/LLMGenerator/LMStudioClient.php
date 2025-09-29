<?php

namespace Anymodule\Agentmodule\Services\LLMGenerator;

use Anymodule\Agentmodule\Interface\ProcessHandlerInterface;
use Anymodule\Agentmodule\Services\OpenAIChat\Interface\MessageMapper;
use Vasenin26\Conversation\Chat;
use Anymodule\Agentmodule\Entity\ProcessingResult;
use Anymodule\Agentmodule\Interface\GPTProcessorInterface;
use Anymodule\Agentmodule\Interface\Tools\ToolsProvider;
use Anymodule\Agentmodule\Utils\Log;
use OpenAI;
use Vasenin26\Conversation\Interface\Conversation;
use Vasenin26\Conversation\Messages\AssistantMessage;

/**
 * @deprecated use OpenAiChat like solution
 */
class LMStudioClient implements GPTProcessorInterface
{
    const MAX_RESULT_ATTEMPT = 3;

    public function __construct(
        private string        $apiKey,
        private string        $model,
        private ToolsProvider $tools,
        private MessageMapper $messageMapper,
    )
    {
    }

    public function process(Conversation $chat, ?ProcessHandlerInterface $processHandler, bool $resultRequired = false): ProcessingResult
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
        $taskAwait = 0;
        $resultAttempt = self::MAX_RESULT_ATTEMPT;

        do {
            Log::info("LLM ask");

            try {
                $messages = $this->messageMapper->mapChat($chat);

                if (empty($messages)) {
                    return new ProcessingResult(
                        true,
                        'Empty chat',
                        $chat,
                        $promptTokens,
                        $completionTokens,
                        $totalTokens
                    );
                }

                $result = $client->chat()->create([
                    'model' => $this->model,
//                    'model' => 'gpt-4.1-nano',
                    'messages' => $messages,
                    'tools' => $this->tools->getMeta()
                ]);
            } catch (\Throwable $exception) {

                Log::warning($exception->getMessage());

                $chat->addMessage($this->messageMapper->mapToInfoMessage($exception->getMessage()));

                return new ProcessingResult(
                    true,
                    null,
                    $chat,
                    $promptTokens,
                    $completionTokens,
                    $totalTokens
                );
            }

            $lastMessage = $result->choices[0]->message;
            $toolCalls = $lastMessage->toolCalls;

            $result = $this->messageMapper->prepareAssistantMessage($result);
            $chat->addMessage(new AssistantMessage(
                $result->getProcessorAnswer()?->message ?? '',
                iterator_to_array($result->getToolCalls()),
            ));

            if ($processHandler) {
                $processHandler->handle(new ProcessingResult(
                    false,
                    $answer,
                    $chat,
                    $promptTokens,
                    $completionTokens,
                    $totalTokens
                ));
            }

            Log::info("LLM Say:" . $lastMessage->content);

            if (empty($toolCalls)) {
                if ($resultRequired) {
                    $resultAttempt--;
                    $chat->addMessage($this->messageMapper->mapToHelpInstructionMessage('Store answer with tools for finish'));
                    Log::info("LLM forgot send result (remember attempt: $resultAttempt)");
                    if($resultAttempt === 0) {
                        $answer = 'A dont know how to send result';
                        $finished = true;
                    }
                } else {
                    $finished = true;
                }
            } else {
                $resultAttempt = self::MAX_RESULT_ATTEMPT;
                foreach ($toolCalls as $toolCall) {

                    Log::info("LLM call tool " . $toolCall->function->name);

                    try {
                        $toolResult = $this->tools->callTool($toolCall->function->name, $toolCall->function->arguments);
                    } catch (\Throwable $exception) {
                        Log::info("Tool Broken");

                        $chat->addMessage($this->messageMapper->mapToToolMessage(
                            false,
                            $toolCall->id,
                            $toolCall->function->name,
                            $toolCall->function->arguments,
                            'This tool has broken',
                        ));

                        continue;
                    }

                    Log::info("Tool OK");

                    if (is_null($toolResult)) {

                        Log::info("Tool Failed");

                        $chat->addMessage($this->messageMapper->mapToToolMessage(
                            false,
                            $toolCall->id,
                            $toolCall->function->name,
                            $toolCall->function->arguments,
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
                        true,
                        $toolCall->id,
                        $toolCall->function->name,
                        $toolCall->function->arguments,
                        $toolResult,
                    ));
                }
            }

            $tokenUsage = $result->getTokenUsage();
            $promptTokens += $tokenUsage->sent;
            $completionTokens += $tokenUsage->received;
            $totalTokens += $tokenUsage->total;

            if ($processHandler && !$finished) {
                $processHandler->handle(new ProcessingResult(
                    false,
                    $answer,
                    $chat,
                    $promptTokens,
                    $completionTokens,
                    $totalTokens
                ));
            }

            if ($finished) {
                $taskAwait = $this->tools->getTodo();
                if ($taskAwait > 0) {
                    Log::info("LLM have unfinished  tasks (count: $taskAwait)");
                    $chat->addMessage(
                        $this->messageMapper->mapToHelpInstructionMessage(
                            "You have {$taskAwait} uncompleted tasks. You need to complete them and mark them with the tool."
                        )
                    );
                }
            }

        } while (!$finished || $taskAwait);

        return new ProcessingResult(
            true,
            $answer,
            $chat,
            $promptTokens,
            $completionTokens,
            $totalTokens
        );
    }
}
