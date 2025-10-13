<?php

namespace Anymodule\Agentmodule\Interface;

use Anymodule\Agentmodule\Interface\Tools\ToolsProviderInterface;
use Anymodule\Agentmodule\Services\ChatAgent\Interface\ChatProcessorInterface;

interface ChatProcessorFactoryInterface
{

    public function createMainProcessor(ToolsProviderInterface $tools): ChatProcessorInterface;
}