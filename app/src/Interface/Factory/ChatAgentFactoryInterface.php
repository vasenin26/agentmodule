<?php

namespace Anymodule\Agentmodule\Interface\Factory;


use Anymodule\Agentmodule\Interface\ActionContract;
use Anymodule\Agentmodule\Interface\ContextActionContract;
use Anymodule\Agentmodule\Interface\Git\GitRepoProviderInterface;
use Anymodule\Agentmodule\Interface\Tools\ToolsProviderInterface;

interface ChatAgentFactoryInterface
{
    public function createAgent(ToolsProviderInterface $tools, GitRepoProviderInterface $repositoryProvider): ActionContract;

    public function createContextAgent(ToolsProviderInterface $tools, GitRepoProviderInterface $repositoryProvider): ContextActionContract;
}