<?php

namespace Anymodule\Agentmodule\Application\ChatAgent\Interface;

use Anymodule\Agentmodule\Application\ChatAgent\DTO\ProcessorAnswer;
use Anymodule\Agentmodule\Application\ChatAgent\DTO\TokenUsage;
use Anymodule\Agentmodule\Application\ChatAgent\DTO\ToolCall;

interface ChatResultInterface
{

    public function getProcessorAnswer(): ?ProcessorAnswer;


    /**
     * @return \Generator<ToolCall>
     */
    public function getToolCalls(): \Generator;


    public function getTokenUsage(): TokenUsage;
}