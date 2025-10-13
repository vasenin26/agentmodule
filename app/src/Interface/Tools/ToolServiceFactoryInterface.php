<?php

namespace Anymodule\Agentmodule\Interface\Tools;

use Anymodule\Agentmodule\Application\ToolsService\ToolsBuilder;
use Anymodule\Agentmodule\Application\ToolsService\ToolsProviderService;
use Anymodule\Agentmodule\Services\RepositoryService\RepositoryProvider;

interface ToolServiceFactoryInterface
{
    public function withMainTools(): ToolsProviderService;

    public function createToolsBuilder(): ToolsBuilder;

    public function createToolsBuilderWithRepository(RepositoryProvider $repositoryProvider): ToolsBuilder;
}
