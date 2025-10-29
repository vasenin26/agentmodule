<?php

namespace Anymodule\Agentmodule\Application\ChatAgent;

use Anymodule\Agentmodule\Application\ChatAgent\DTO\TokenUsage;
use Anymodule\Agentmodule\Application\ChatAgent\DTO\ToolCall;
use Anymodule\Agentmodule\Application\ChatAgent\Exception\CompressorException;
use Anymodule\Agentmodule\Application\ChatAgent\Exception\LoopLimitReachedException;
use Anymodule\Agentmodule\Application\ChatAgent\Exception\ToolCallLimitReachedException;
use Anymodule\Agentmodule\Application\ChatAgent\Interface\ChatResultInterface;
use Anymodule\Agentmodule\Application\ChatAgent\Interface\ContextConversationProcessorInterface;
use Anymodule\Agentmodule\Entity\ContextConversation;
use Anymodule\Agentmodule\Entity\ProcessingResult;
use Anymodule\Agentmodule\Interface\ContextActionContract;
use Anymodule\Agentmodule\Interface\ConversationCompressorInterface;
use Anymodule\Agentmodule\Interface\Tools\ToolsProviderInterface;
use Anymodule\Agentmodule\Services\OpenAIChat\Exception\ContextOverloadException;
use Anymodule\Agentmodule\Utils\Log;
use Vasenin26\Conversation\Messages\AssistantMessage;
use Vasenin26\Conversation\Messages\DisappearingMessage;
use Vasenin26\Conversation\Messages\ToolMessage;
use Vasenin26\Conversation\Messages\UserMessage;

class ContextAgent implements ContextActionContract
{
    const LOOP_LIMIT = 50;
    const TOOL_CALL_LIMIT = 3;

    private int $promptTokens = 0;
    private int $completionTokens = 0;
    private int $totalTokens = 0;

    public function __construct(
        private ContextConversationProcessorInterface $processor,
        private ConversationCompressorInterface       $compressor,
        private ?ToolsProviderInterface               $tools,
    )
    {
    }

    public function execute(ContextConversation $conversation): \Generator
    {
        $this->promptTokens = 0;
        $this->completionTokens = 0;
        $this->totalTokens = 0;

        $compressed = false;

        do {
            try {
                foreach ($this->process($conversation) as $processingResult) {
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

                $contextChat = $this->compressor->compress($conversation->conversation);
                $conversation = new ContextConversation($conversation->context, $contextChat);
                $compressed = true;
            } catch (ToolCallLimitReachedException $exception) {
                Log::error('Tool call limit reached');
                $conversation->conversation->addMessage(new UserMessage('You seem confused. Think about it and continue working.'));
            } catch (LoopLimitReachedException $exception) {
                Log::error('Loop limit reached');
                $conversation->conversation->addMessage(new UserMessage("It seems you've been working too long. Check your to-do list and move on."));
            }
        } while (true);
    }

    private function process(ContextConversation $contextConversation): \Generator
    {

        Log::info("Available LLM tools", array_map(fn($i) => $i['function']['name'], $this->tools?->getMeta() ?? []));

        $finished = false;
        $toolCallCounters = [];
        $stepCounter = self::LOOP_LIMIT;

        do {
            Log::info("Call LLM");

            $result = $this->processor->process($contextConversation, $this->tools);

            $answerMessage = $this->prepareAssistantMessage($result);
            $contextConversation->conversation->addMessage($answerMessage);

            Log::info("LLM ok");

            $this->calculateUsage($result->getTokenUsage());

            yield $this->prepareResult(false, $result, $contextConversation);

            $toolCalls = iterator_to_array($result->getToolCalls());
            $calledToolNames = [];

            if (empty($toolCalls)) {
                Log::info("LLM finished");
                $finished = true;
            } else {
                foreach ($toolCalls as $toolCall) {
                    $calledToolNames[] = $toolCall->name;
                    $toolCallCounters[$toolCall->name] = ($toolCallCounters[$toolCall->name] ?? 0) + 1;

                    $toolResult = $this->callTool($toolCall);

                    if (is_null($toolResult)) {
                        continue;
                    }

                    $contextConversation->conversation->addMessage($toolResult);
                }
            }

            Log::info("Process handler");

            yield $this->prepareResult(false, $result, $contextConversation);

            foreach (array_keys($toolCallCounters) as $toolCallName) {
                if ($toolCallCounters[$toolCallName] >= self::TOOL_CALL_LIMIT) {
                    throw new ToolCallLimitReachedException();
                }

                if (in_array($toolCallName, $calledToolNames)) {
                    $toolCallCounters[$toolCallName] = 0;
                }
            }

            $stepCounter--;

            if ($stepCounter === 0) {
                throw new LoopLimitReachedException();
            }

        } while (!$finished);

        yield $this->prepareResult(true, $result, $contextConversation);
    }

    private function calculateUsage(TokenUsage $usage): void
    {
        $this->promptTokens += $usage->sent;
        $this->completionTokens += $usage->received;
        $this->totalTokens += $usage->total;
    }

    private function prepareAssistantMessage(ChatResultInterface $result): AssistantMessage
    {
        return new AssistantMessage(
            $result->getProcessorAnswer()?->message ?? '',
            array_map(fn(ToolCall $tc) => (array)$tc, iterator_to_array($result->getToolCalls())),
        );
    }

    private function calculateContextFill(TokenUsage $usage): float
    {
        if ($usage->total === 0) {
            return 0;
        }

        return round(($usage->sent / $this->processor->contextSize()), 2);
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

    private function prepareResult(bool $completed, ChatResultInterface $result, ContextConversation $contextConversation): ProcessingResult
    {
        return new ProcessingResult(
            $completed,
            null,
            $contextConversation->conversation,
            $contextConversation->context,
            $this->processor->getModelMeta()->name,
            $this->calculateContextFill($result->getTokenUsage()),
            $this->promptTokens,
            $this->completionTokens,
            $this->totalTokens
        );
    }
}