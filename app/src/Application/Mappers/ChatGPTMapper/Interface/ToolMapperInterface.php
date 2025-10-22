<?php

namespace Anymodule\Agentmodule\Application\Mappers\ChatGPTMapper\Interface;

use Vasenin26\Conversation\Messages\ToolMessage;

interface ToolMapperInterface
{
    public function supports(ToolMessage $tool): bool;
    public function map(ToolMessage $message): string;
}