<?php

namespace Anymodule\Agentmodule\Interface;

use Anymodule\Agentmodule\Application\ChatAgent\Interface\ChatResultInterface;
use Anymodule\Agentmodule\Entity\ContextConversation;
use Anymodule\Agentmodule\Interface\Tools\ToolsProviderInterface;

interface ContextConversationProcessorInterface
{
    public function process(ContextConversation $contextConversation, ?ToolsProviderInterface $tools): ChatResultInterface;
}