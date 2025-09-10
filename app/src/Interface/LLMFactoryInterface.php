<?php

namespace Anymodule\Agentmodule\Interface;

use Anymodule\Agentmodule\Services\ToolsService\ToolsService;

interface LLMFactoryInterface
{

    public function createChat(ToolsService $toolsService): GPTProcessorInterface;
}