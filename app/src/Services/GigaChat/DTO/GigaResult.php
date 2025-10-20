<?php

namespace Anymodule\Agentmodule\Services\GigaChat\DTO;

use Anymodule\Agentmodule\Application\ChatAgent\DTO\ProcessorAnswer;
use Anymodule\Agentmodule\Application\ChatAgent\DTO\TokenUsage;
use Anymodule\Agentmodule\Application\ChatAgent\Interface\ChatResultInterface;
use Anymodule\Agentmodule\Services\OpenAIChat\DTO\OpenAiResult;

class GigaResult implements ChatResultInterface
{
    private bool $success;

    public function __construct(
        private $message,
    )
    {
    }

    public function getProcessorAnswer(): ?ProcessorAnswer
    {
        return new ProcessorAnswer(
            $this->message,
        );
    }

    public function getToolCalls(): \Generator
    {
        if (false) {
            yield null;
        }
    }

    public function getTokenUsage(): TokenUsage
    {
        return new TokenUsage(0, 0, 0);
    }

    public static function empty(): self
    {
        return self::error(null);
    }

    public static function error(?string $message): self
    {
        $o = new self(
            $message
        );

        $o->success = false;

        return $o;
    }
}