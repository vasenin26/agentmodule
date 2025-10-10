<?php

namespace Anymodule\Agentmodule\Interface\Tools;

use Anymodule\Agentmodule\Services\RepositoryService\RepositoryProvider;
use Anymodule\Agentmodule\Services\ToolsService\ToolsBuilder;
use Anymodule\Agentmodule\Services\ToolsService\ToolsProviderInterfaceService;

interface ToolServiceFactoryInterface
{
    public function withMainTools(): ToolsProviderInterfaceService;

    public function createToolsBuilder(): ToolsBuilder;

    public function createToolsBuilderWithRepository(RepositoryProvider $repositoryProvider): ToolsBuilder;
}
