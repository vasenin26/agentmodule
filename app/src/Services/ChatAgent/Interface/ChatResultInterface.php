<?php

namespace Anymodule\Agentmodule\Services\ChatAgent\Interface;

use Anymodule\Agentmodule\Services\ChatAgent\DTO\ProcessorAnswer;
use Anymodule\Agentmodule\Services\ChatAgent\DTO\TokenUsage;
use Anymodule\Agentmodule\Services\ChatAgent\DTO\ToolCall;

interface ChatResultInterface
{

    public function getProcessorAnswer(): ?ProcessorAnswer;


    /**
     * @return \Generator<ToolCall>
     */
    public function getToolCalls(): \Generator;


    public function getTokenUsage(): TokenUsage;
}