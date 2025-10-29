<?php

namespace Anymodule\Agentmodule\Interface\Factory;

use Anymodule\Agentmodule\Application\ChatAgent\Interface\ChatProcessorInterface;
use Anymodule\Agentmodule\Application\ChatAgent\Interface\ContextConversationProcessorInterface;
use Anymodule\Agentmodule\Interface\Git\GitRepoProviderInterface;
use Anymodule\Agentmodule\Interface\Tools\ToolsProviderInterface;

interface ChatProcessorFactoryInterface
{

    /**
     * @deprecated use ChatProcessorFactoryInterface::createModelContextProcessor
     */
    public function createContextProcessor(ToolsProviderInterface $tools, GitRepoProviderInterface $repositoryProvider): ContextConversationProcessorInterface;

    public function createModelContextProcessor(?string $modelName, ToolsProviderInterface $tools, GitRepoProviderInterface $repositoryProvider);
}