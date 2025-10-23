<?php

namespace Anymodule\Agentmodule\Interface;


use Anymodule\Agentmodule\Interface\Git\GitRepoProviderInterface;
use Anymodule\Agentmodule\Interface\Tools\ToolsProviderInterface;

interface ChatAgentFactoryInterface
{
    public function createAgent(ToolsProviderInterface $tools, GitRepoProviderInterface $repositoryProvider): ActionContract;

    public function createContextAgent(ToolsProviderInterface $tools, GitRepoProviderInterface $repositoryProvider): ContextActionContract;
}