<?php

namespace Anymodule\Agentmodule\Application\ChatAgent\Interface;

use Anymodule\Agentmodule\Entity\ModelMeta;
use Anymodule\Agentmodule\Interface\Tools\ToolsProviderInterface;
use Vasenin26\Conversation\Chat;

interface ChatProcessorInterface
{
    public function contextSize(): int;
    public function getModelMeta(): ModelMeta;
    public function process(Chat $chat, ?ToolsProviderInterface $tools): ChatResultInterface;
}