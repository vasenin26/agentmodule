<?php

namespace Anymodule\Agentmodule\Services\StupidJoe\DTO;

use Anymodule\Agentmodule\Application\ChatAgent\DTO\ProcessorAnswer;
use Anymodule\Agentmodule\Application\ChatAgent\DTO\TokenUsage;
use Anymodule\Agentmodule\Application\ChatAgent\DTO\ToolCall;
use Anymodule\Agentmodule\Application\ChatAgent\Interface\ChatResultInterface;

final class StupidResult implements ChatResultInterface
{
    public function __construct(
        private string $message,
        private int $sent = 0,
        private int $received = 0,
        private int $total = 0,
        private array $toolCalls = [],
    )
    {
    }

    public function getProcessorAnswer(): ?ProcessorAnswer
    {
        return new ProcessorAnswer($this->message);
    }

    /**
     * @return \Generator<ToolCall>
     */
    public function getToolCalls(): \Generator
    {
        foreach ($this->toolCalls as $toolCall) {
            yield new ToolCall($toolCall['id'], $toolCall['name'], $toolCall['arguments']);
        }
    }

    public function getTokenUsage(): TokenUsage
    {
        return new TokenUsage(
            $this->sent,
            $this->received,
            $this->total,
        );
    }
}
