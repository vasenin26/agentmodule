<?php

namespace Anymodule\Agentmodule\Interface;

use Anymodule\Agentmodule\Services\ToolsService\ToolsProviderService;

interface LLMFactoryInterface
{

    public function createChat(ToolsProviderService $toolsService): GPTProcessorInterface;
}