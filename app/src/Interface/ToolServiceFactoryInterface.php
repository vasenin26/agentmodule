<?php

namespace Anymodule\Agentmodule\Interface;

use Anymodule\Agentmodule\Services\ToolsService\ToolsService;

interface ToolServiceFactoryInterface
{
    public function withAllTools(): ToolsService;
    public function withAllToolsForProject(int $projectId): ToolInterface;
}
