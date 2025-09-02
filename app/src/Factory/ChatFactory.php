<?php

namespace Anymodule\Agentmodule\Factory;

use Anymodule\Agentmodule\Interface\ChatFactoryInterface;
use Anymodule\Agentmodule\Interface\GPTProcessorInterface;
use Anymodule\Agentmodule\Services\ToolsService\ToolsService;

class ChatFactory implements ChatFactoryInterface
{

    public function createChat(ToolsService $toolsService): GPTProcessorInterface
    {
        // TODO: Implement createChat() method.
    }
}