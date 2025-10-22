<?php

namespace Anymodule\Agentmodule\Application\Mappers\ChatGPTMapper\Interface;

use Vasenin26\Conversation\Messages\ToolMessage;

interface ToolResultOptimizationInterface
{
    public function supports(ToolMessage $message): bool;
    public function optimize(ToolMessage $message): ToolMessage;
}