<?php

namespace Anymodule\Agentmodule\Interface\Factory;

use Anymodule\Agentmodule\Application\ChatAgent\Interface\ChatProcessorInterface;
use Anymodule\Agentmodule\Application\ChatAgent\Interface\ContextConversationProcessorInterface;
use Anymodule\Agentmodule\Interface\Git\GitRepoProviderInterface;
use Anymodule\Agentmodule\Interface\Tools\ToolsProviderInterface;

interface ChatProcessorFactoryInterface
{

    public function createMainProcessor(ToolsProviderInterface $tools, GitRepoProviderInterface $repositoryProvider): ChatProcessorInterface;

    public function createSummaryProcessor(GitRepoProviderInterface $repositoryProvider): ChatProcessorInterface;

    public function createContextProcessor(ToolsProviderInterface $tools, GitRepoProviderInterface $repositoryProvider): ContextConversationProcessorInterface;
}