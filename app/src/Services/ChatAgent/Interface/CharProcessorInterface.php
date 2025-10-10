<?php

namespace Anymodule\Agentmodule\Services\ChatAgent\Interface;

use Anymodule\Agentmodule\Interface\Tools\ToolsProvider;
use Vasenin26\Conversation\Chat;

interface CharProcessorInterface
{
    public function contextSize(): int;
    public function process(Chat $chat, ToolsProvider $tools): ChatResultInterface;
}