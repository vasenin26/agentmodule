<?php

namespace Anymodule\Agentmodule\Interface;

use Anymodule\Agentmodule\Application\ChatAgent\Interface\ChatProcessorInterface;
use Anymodule\Agentmodule\Interface\Tools\ToolsProviderInterface;

interface ChatProcessorFactoryInterface
{

    public function createMainProcessor(ToolsProviderInterface $tools): ChatProcessorInterface;

    public function createSummaryProcessor(): ChatProcessorInterface;
}