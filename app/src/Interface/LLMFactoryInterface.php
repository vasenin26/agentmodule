<?php

namespace Anymodule\Agentmodule\Interface;

use Anymodule\Agentmodule\Services\ToolsService\ToolsService;

interface ChatFactoryInterface
{

    public function createChat(ToolsService $toolsService): GPTProcessorInterface;
}