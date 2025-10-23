<?php

namespace Anymodule\Agentmodule\Services\StupidJoe;

use Anymodule\Agentmodule\Application\ChatAgent\Interface\ChatProcessorInterface;
use Anymodule\Agentmodule\Application\ChatAgent\Interface\ChatResultInterface;
use Anymodule\Agentmodule\Entity\ModelMeta;
use Anymodule\Agentmodule\Interface\Tools\ToolsProviderInterface;
use Anymodule\Agentmodule\Services\StupidJoe\Service\StupidProcessorService;
use Vasenin26\Conversation\Interface\Conversation;

class ChatProcessor implements ChatProcessorInterface
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

    public function process(Conversation $chat, ?ToolsProviderInterface $tools): ChatResultInterface
    {
        return $this->stupidProcessorService->generateResponse($tools, $this->modelMeta, $chat);
    }
}
