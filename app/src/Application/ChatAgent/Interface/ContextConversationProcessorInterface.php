<?php

namespace Anymodule\Agentmodule\Application\ChatAgent\Interface;

use Anymodule\Agentmodule\Entity\ModelMeta;
use Anymodule\Agentmodule\Interface\Tools\ToolsProviderInterface;
use Anymodule\Agentmodule\Entity\ContextConversation;

interface ContextConversationProcessorInterface
{
    public function contextSize(): int;
    public function getModelMeta(): ModelMeta;
    public function process(ContextConversation $contextConversation, ?ToolsProviderInterface $tools): ChatResultInterface;
}