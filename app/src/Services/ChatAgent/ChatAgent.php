<?php

namespace Anymodule\Agentmodule\Services\ChatAgent;

use Anymodule\Agentmodule\Entity\ProcessingResult;
use Anymodule\Agentmodule\Interface\ChatAgentInterface;
use Anymodule\Agentmodule\Interface\Tools\LLMTools;
use Anymodule\Agentmodule\Services\ChatAgent\Interface\CharProcessorInterface;
use Anymodule\Agentmodule\Utils\Log;
use Vasenin26\Conversation\Chat;
use Vasenin26\Conversation\Messages\AssistantMessage;
use Vasenin26\Conversation\Messages\ToolMessage;

class ChatAgent implements ChatAgentInterface
{
    public function __construct(
        private CharProcessorInterface $chatProcessor,
        private LLMTools               $tools,
    )
    {
    }

    public function process(Chat $chat, $processHandler = null): ProcessingResult
    {
        $promptTokens = 0;
        $completionTokens = 0;
        $totalTokens = 0;

        Log::info("Available LLM tools", array_map(fn($i) => $i['function']['name'], $this->tools->getMeta()));

        $answer = null;
        $finished = false;

        do {
            Log::info("Call LLM");
            $result = $this->chatProcessor->process($chat, $this->tools);

            $answerMessage = $this->prepareAssistantMessage($result);
            $chat->addMessage($answerMessage);
            Log::info("LLM ok");

            if ($processHandler) {
                Log::info("Process handler");
                $state = $processHandler(new ProcessingResult(
                    false,
                    $answer,
                    $chat,
                    $promptTokens,
                    $completionTokens,
                    $totalTokens
                ));

                if ($state === 'stopped') {
                    break;
                }
            }

            $toolCalls = iterator_to_array($result->getToolCalls());

            if (empty($toolCalls)) {
                Log::info("LLM finished");
                $answer = $answerMessage->content;
                $finished = true;
            } else {
                foreach ($toolCalls as $toolCall) {

                    Log::info("Call tool: {$toolCall->name}");

                    try {
                        $toolResult = $this->tools->callTool($toolCall->name, $toolCall->arguments);

                        Log::info("Tool result: {$toolResult}");
                    } catch (\Throwable $exception) {
                        $chat->addMessage(new ToolMessage(
                            false,
                            $toolCall->id,
                            $toolCall->name,
                            $toolCall->arguments,
                            'This tool has broken',
                        ));

                        Log::info("Tool error: {$exception->getMessage()}");

                        continue;
                    }

                    if (is_null($toolResult)) {
                        $chat->addMessage(new ToolMessage(
                            false,
                            $toolCall->id,
                            $toolCall->name,
                            $toolCall->arguments,
                            'Tools was call with wrong parameters',
                        ));

                        continue;
                    }

                    $chat->addMessage(new ToolMessage(
                        false,
                        $toolCall->id,
                        $toolCall->name,
                        $toolCall->arguments,
                        $toolResult,
                    ));
                }
            }

            $usage = $result->getTokenUsage();

            $promptTokens += $usage->sent;
            $completionTokens += $usage->received;
            $totalTokens += $usage->total;

            if ($processHandler && !$finished) {
                Log::info("Process handler");
                $status = $processHandler(new ProcessingResult(
                    false,
                    $answer,
                    $chat,
                    $promptTokens,
                    $completionTokens,
                    $totalTokens
                ));

                if ($status === 'stopped') {
                    break;
                }
            }

        } while (!$finished);

        return new ProcessingResult(
            true,
            $answer,
            $chat,
            $promptTokens,
            $completionTokens,
            $totalTokens
        );
    }

    private function prepareAssistantMessage(Interface\ChatResultInterface $result): AssistantMessage
    {
        return new AssistantMessage(
            $result->getProcessorAnswer()?->message ?? '',
                iterator_to_array($result->getToolCalls()),
        );
    }
}