<?php

namespace Anymodule\Agentmodule\Services\StupidJoe;

use Anymodule\Agentmodule\Application\ChatAgent\Interface\ChatResultInterface;
use Anymodule\Agentmodule\Application\ChatAgent\Interface\ContextConversationProcessorInterface;
use Anymodule\Agentmodule\Entity\ContextConversation;
use Anymodule\Agentmodule\Entity\ModelMeta;
use Anymodule\Agentmodule\Interface\Tools\ToolsProviderInterface;
use Anymodule\Agentmodule\Services\StupidJoe\Service\StupidProcessorService;

class ContextConversationProcessor implements ContextConversationProcessorInterface
{

    public function __construct(
        private ModelMeta $modelMeta,
        private StupidProcessorService $stupidProcessorService,
    )
    {
    }

    public function contextSize(): int
    {
        return $this->getModelMeta()->contextSize;
    }

    public function getModelMeta(): ModelMeta
    {
        return $this->modelMeta;
    }

    public function process(ContextConversation $contextConversation, ?ToolsProviderInterface $tools): ChatResultInterface
    {
        return $this->stupidProcessorService->generateResponse($tools, 'general', $contextConversation);
    }
}