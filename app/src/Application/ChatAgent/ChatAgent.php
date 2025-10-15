<?php

namespace Anymodule\Agentmodule\Application\ChatAgent;

use Anymodule\Agentmodule\Application\ChatAgent\DTO\TokenUsage;
use Anymodule\Agentmodule\Application\ChatAgent\DTO\ToolCall;
use Anymodule\Agentmodule\Application\ChatAgent\Exception\CompressorException;
use Anymodule\Agentmodule\Application\ChatAgent\Interface\ChatProcessorInterface;
use Anymodule\Agentmodule\Entity\ProcessingResult;
use Anymodule\Agentmodule\Interface\ActionContract;
use Anymodule\Agentmodule\Interface\ConversationCompressorInterface;
use Anymodule\Agentmodule\Interface\Tools\ToolsProviderInterface;
use Anymodule\Agentmodule\Services\OpenAIChat\Exception\ContextOverloadException;
use Anymodule\Agentmodule\Utils\Log;
use Vasenin26\Conversation\Interface\Conversation;
use Vasenin26\Conversation\Messages\AssistantMessage;
use Vasenin26\Conversation\Messages\ToolMessage;

class ChatAgent implements ActionContract
{
    private int $promptTokens = 0;
    private int $completionTokens = 0;
    private int $totalTokens = 0;

    public function __construct(
        private ChatProcessorInterface          $chatProcessor,
        private ConversationCompressorInterface $compressor,
        private ?ToolsProviderInterface         $tools,
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
                    throw new CompressorException(
                        'Failed to compress messages',
                        $exception->getCode(),
                        $exception
                    );
                }

                $contextChat = $this->compressor->compress($conversation);
                $compressed = true;
            }
        } while (true);
    }

    private function process(Conversation $conversation): \Generator
    {
        $this->promptTokens = 0;
        $this->completionTokens = 0;
        $this->totalTokens = 0;

        Log::info("Available LLM tools", array_map(fn($i) => $i['function']['name'], $this->tools?->getMeta() ?? []));

        $answer = null;
        $finished = false;

        do {
            Log::info("Call LLM");

            $result = $this->chatProcessor->process($conversation, $this->tools);

            $answerMessage = $this->prepareAssistantMessage($result);
            $conversation->addMessage($answerMessage);

            Log::info("LLM ok");

            yield $this->prepareResult(false, $result, $answer, $conversation);

            $toolCalls = iterator_to_array($result->getToolCalls());

            if (empty($toolCalls)) {
                Log::info("LLM finished");
                $answer = $answerMessage->content;
                $finished = true;
            } else {
                foreach ($toolCalls as $toolCall) {
                    $toolResult = $this->callTool($toolCall);

                    if (is_null($toolResult)) {
                        continue;
                    }

                    $conversation->addMessage($toolResult);
                }
            }

            $this->calculateUsage($result->getTokenUsage());

            Log::info("Process handler");

            yield $this->prepareResult(false, $result, $answer, $conversation);

        } while (!$finished);

        yield $this->prepareResult(true, $result, $answer, $conversation);
    }

    private function calculateUsage(TokenUsage $usage): void
    {
        $this->promptTokens += $usage->sent;
        $this->completionTokens += $usage->received;
        $this->totalTokens += $usage->total;
    }

    private function prepareAssistantMessage(\Anymodule\Agentmodule\Application\ChatAgent\Interface\ChatResultInterface $result): AssistantMessage
    {
        return new AssistantMessage(
            $result->getProcessorAnswer()?->message ?? '',
            array_map(fn(ToolCall $tc) => (array)$tc, iterator_to_array($result->getToolCalls())),
        );
    }

    private function calculateContextFill(\Anymodule\Agentmodule\Application\ChatAgent\DTO\TokenUsage $usage): float
    {
        if ($usage->total === 0) {
            return 0;
        }

        return round(($usage->sent / $this->chatProcessor->contextSize()), 2);
    }

    private function callTool(ToolCall $toolCall): ?ToolMessage
    {
        Log::info("Call tool: {$toolCall->name}");

        try {
            $toolResult = $this->tools->callTool($toolCall->name, $toolCall->arguments);

            Log::info("Tool result: {$toolResult->message}");
        } catch (\Throwable $exception) {
            Log::info("Tool error: {$exception->getMessage()}");

            return new ToolMessage(
                false,
                $toolCall->id,
                $toolCall->name,
                $toolCall->arguments,
                'This tool has broken: ' . $toolCall->name,
            );
        }

        if (is_null($toolResult)) {
            return new ToolMessage(
                false,
                $toolCall->id,
                $toolCall->name,
                $toolCall->arguments,
                'Tools was call with wrong parameters',
            );
        }

        return new ToolMessage(
            $toolResult->status,
            $toolCall->id,
            $toolCall->name,
            $toolCall->arguments,
            (string)$toolResult,
        );
    }

    private function prepareResult($completed, $result, $answer, $conversation): ProcessingResult
    {
        return new ProcessingResult(
            $completed,
            $answer,
            $conversation,
            $this->chatProcessor->getModelMeta()->name,
            $this->calculateContextFill($result->usage),
            $this->promptTokens,
            $this->completionTokens,
            $this->totalTokens
        );
    }
}