<?php

namespace Anymodule\Agentmodule\Services\ChatGPTMapper\Interface;

use Vasenin26\Conversation\Messages\ToolMessage;

interface ToolResultOptimizationInterface
{
    public function supports(ToolMessage $message): bool;
    public function optimize(ToolMessage $message): ToolMessage;
}