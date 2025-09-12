<?php

namespace Anymodule\Agentmodule\Interface\Tools;

use Anymodule\Agentmodule\Services\RepositoryService\RepositoryProvider;
use Anymodule\Agentmodule\Services\ToolsService\ToolsBuilder;
use Anymodule\Agentmodule\Services\ToolsService\ToolsService;

interface ToolServiceFactoryInterface
{
    public function withMainTools(): ToolsService;

    public function createToolsBuilder(): ToolsBuilder;

    public function createToolsBuilderWithRepository(RepositoryProvider $repositoryProvider): ToolsBuilder;
}
