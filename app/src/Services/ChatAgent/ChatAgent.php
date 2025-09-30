<?php

namespace Anymodule\Agentmodule\Services\ChatAgent;

use Anymodule\Agentmodule\Entity\ProcessingResult;
use Anymodule\Agentmodule\Interface\ActionContract;
use Anymodule\Agentmodule\Interface\Tools\ToolsProvider;
use Anymodule\Agentmodule\Services\ChatAgent\DTO\ToolCall;
use Anymodule\Agentmodule\Services\ChatAgent\Interface\CharProcessorInterface;
use Anymodule\Agentmodule\Utils\Log;
use Vasenin26\Conversation\Interface\Conversation;
use Vasenin26\Conversation\Messages\AssistantMessage;
use Vasenin26\Conversation\Messages\ToolMessage;

class ChatAgent implements ActionContract
{
    public function __construct(
        private CharProcessorInterface $chatProcessor,
        private ToolsProvider          $tools,
    )
    {
    }

    public function execute(Conversation $conversation): \Generator
    {
        $promptTokens = 0;
        $completionTokens = 0;
        $totalTokens = 0;

        Log::info("Available LLM tools", array_map(fn($i) => $i['function']['name'], $this->tools->getMeta()));

        $answer = null;
        $finished = false;

        do {
            Log::info("Call LLM");
            $result = $this->chatProcessor->process($conversation, $this->tools);

            $answerMessage = $this->prepareAssistantMessage($result);
            $conversation->addMessage($answerMessage);

            Log::info("LLM ok");

            yield new ProcessingResult(
                false,
                $answer,
                $conversation,
                $promptTokens,
                $completionTokens,
                $totalTokens
            );

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

                        Log::info("Tool result: {$toolResult->message}");
                    } catch (\Throwable $exception) {
                        $conversation->addMessage(new ToolMessage(
                            false,
                            $toolCall->id,
                            $toolCall->name,
                            $toolCall->arguments,
                            'This tool has broken: ' . $toolCall->name,
                        ));

                        Log::info("Tool error: {$exception->getMessage()}");

                        continue;
                    }

                    if (is_null($toolResult)) {
                        $conversation->addMessage(new ToolMessage(
                            false,
                            $toolCall->id,
                            $toolCall->name,
                            $toolCall->arguments,
                            'Tools was call with wrong parameters',
                        ));

                        continue;
                    }

                    $conversation->addMessage(new ToolMessage(
                        $toolResult->status,
                        $toolCall->id,
                        $toolCall->name,
                        $toolCall->arguments,
                        (string)$toolResult,
                    ));
                }
            }

            $usage = $result->getTokenUsage();

            $promptTokens += $usage->sent;
            $completionTokens += $usage->received;
            $totalTokens += $usage->total;

            Log::info("Process handler");
            yield new ProcessingResult(
                false,
                $answer,
                $conversation,
                $promptTokens,
                $completionTokens,
                $totalTokens
            );

        } while (!$finished);

        yield new ProcessingResult(
            true,
            $answer,
            $conversation,
            $promptTokens,
            $completionTokens,
            $totalTokens
        );
    }

    private function prepareAssistantMessage(Interface\ChatResultInterface $result): AssistantMessage
    {
        return new AssistantMessage(
            $result->getProcessorAnswer()?->message ?? '',
            array_map(fn(ToolCall $tc) => (array)$tc, iterator_to_array($result->getToolCalls())),
        );
    }
}