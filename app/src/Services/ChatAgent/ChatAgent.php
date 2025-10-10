<?php

namespace Anymodule\Agentmodule\Services\ChatAgent;

use Anymodule\Agentmodule\Entity\ProcessingResult;
use Anymodule\Agentmodule\Interface\ActionContract;
use Anymodule\Agentmodule\Interface\ConversationCompressorInterface;
use Anymodule\Agentmodule\Interface\Tools\ToolsProviderInterface;
use Anymodule\Agentmodule\Services\ChatAgent\DTO\ToolCall;
use Anymodule\Agentmodule\Services\ChatAgent\Exception\ContextOverloadException;
use Anymodule\Agentmodule\Services\ChatAgent\Interface\CharProcessorInterface;
use Anymodule\Agentmodule\Utils\Log;
use Vasenin26\Conversation\Interface\Conversation;
use Vasenin26\Conversation\Messages\AssistantMessage;
use Vasenin26\Conversation\Messages\ToolMessage;

class ChatAgent implements ActionContract
{
    public function __construct(
        private CharProcessorInterface          $chatProcessor,
        private ConversationCompressorInterface $compressor,
        private ToolsProviderInterface          $tools,
    )
    {
    }

    public function execute(Conversation $conversation): \Generator
    {
        $contextChat = $conversation;
        $compressed = false;

        do {
            try {
                foreach ($this->process($contextChat) as $processingResult) {
                    $compressed = false;
                    yield $processingResult;
                }

                break;
            } catch (ContextOverloadException $exception) {
                if ($compressed) {
                    throw $exception;
                }

                $contextChat = $this->compressor->compress($conversation);
                $compressed = true;
            }
        } while (true);
    }

    private function process(Conversation $conversation): \Generator
    {
        $promptTokens = 0;
        $completionTokens = 0;
        $totalTokens = 0;

        Log::info("Available LLM tools", array_map(fn($i) => $i['function']['name'], $this->tools->getMeta()));

        $answer = null;
        $finished = false;
        $contextFill = 0;

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
                $contextFill,
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

            if ($usage->total > $this->chatProcessor->contextSize()) {
                throw new ContextOverloadException();
            }

            $contextFill = $this->calculateContextFill($usage);

            $promptTokens += $usage->sent;
            $completionTokens += $usage->received;
            $totalTokens += $usage->total;

            Log::info("Process handler");

            yield new ProcessingResult(
                false,
                $answer,
                $conversation,
                $contextFill,
                $promptTokens,
                $completionTokens,
                $totalTokens
            );

        } while (!$finished);

        yield new ProcessingResult(
            true,
            $answer,
            $conversation,
            $contextFill,
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

    private function calculateContextFill(DTO\TokenUsage $usage): float
    {
        if($usage->total === 0) {
            return 0;
        }

        return round(($usage->sent / $this->chatProcessor->contextSize()), 2);
    }
}