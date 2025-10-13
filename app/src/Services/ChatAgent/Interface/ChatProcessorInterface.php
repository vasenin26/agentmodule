<?php

namespace Anymodule\Agentmodule\Services\ChatAgent\Interface;

use Anymodule\Agentmodule\Interface\Tools\ToolsProviderInterface;
use Vasenin26\Conversation\Chat;

interface ChatProcessorInterface
{
    public function contextSize(): int;
    public function process(Chat $chat, ToolsProviderInterface $tools): ChatResultInterface;
}