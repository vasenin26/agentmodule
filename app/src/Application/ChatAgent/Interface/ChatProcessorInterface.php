<?php

namespace Anymodule\Agentmodule\Application\ChatAgent\Interface;

use Anymodule\Agentmodule\Entity\ModelMeta;
use Anymodule\Agentmodule\Interface\Tools\ToolsProviderInterface;
use Anymodule\Agentmodule\Services\OpenAIChat\Exception\ContextOverloadException;
use Vasenin26\Conversation\Interface\Conversation;

interface ChatProcessorInterface
{
    public function contextSize(): int;
    public function getModelMeta(): ModelMeta;

    /**
     * @throws ContextOverloadException
     */
    public function process(Conversation $chat, ?ToolsProviderInterface $tools): ChatResultInterface;
}