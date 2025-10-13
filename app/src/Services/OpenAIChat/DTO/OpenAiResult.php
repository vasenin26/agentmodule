<?php

namespace Anymodule\Agentmodule\Services\OpenAIChat\DTO;

use Anymodule\Agentmodule\Application\ChatAgent\DTO\ProcessorAnswer;
use Anymodule\Agentmodule\Application\ChatAgent\DTO\TokenUsage;
use Anymodule\Agentmodule\Application\ChatAgent\DTO\ToolCall;
use Anymodule\Agentmodule\Application\ChatAgent\Interface\ChatResultInterface;

final class OpenAiResult implements ChatResultInterface
{
    private bool $success = false;

    /**
     * @param string|null $message
     * @param array $toolCall
     */
    public function __construct(
        private ?string $message,
        private array   $toolCall = [],
        private int     $sent = 0,
        private int     $received = 0,
        private int     $total = 0,
    )
    {
    }

    public static function empty(): self
    {
        return self::error(null);
    }

    public static function error(?string $message): self
    {
        $o = new OpenAiResult(
            $message
        );
        $o->success = false;

        return $o;
    }

    public function getProcessorAnswer(): ?ProcessorAnswer
    {
        if (is_null($this->message)) {
            return null;
        }

        return new ProcessorAnswer($this->message);
    }

    /**
     * @return \Generator<ToolCall>
     */
    public function getToolCalls(): \Generator
    {
        foreach ($this->toolCall as $toolCall) {
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