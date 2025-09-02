<?php

namespace Anymodule\Agentmodule\Interface;

use Anymodule\Agentmodule\Services\ToolsService\ToolsService;

interface ToolServiceFactoryInterface
{
    public function withMainTools(): ToolsService;
    public function withAllToolsForProject(int $projectId): ToolsService;
}
