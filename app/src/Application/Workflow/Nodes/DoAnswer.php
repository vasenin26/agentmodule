<?php

namespace Anymodule\Agentmodule\Application\Workflow\Nodes;

use Anymodule\Agentmodule\Application\Workflow\Interface\NodeProcessorInterface;
use Anymodule\Agentmodule\Interface\Factory\ChatAgentFactoryInterface;
use Anymodule\Agentmodule\Interface\Git\GitRepoProviderInterface;
use Anymodule\Agentmodule\Services\Workflows\Interface\Context;

class DoAnswer implements NodeProcessorInterface
{
    public function __construct(
        private ChatAgentFactoryInterface $chatAgentFactory,
        private GitRepoProviderInterface  $gitRepoProvider,
    )
    {
    }

    public function process(Context $ctx): \Generator
    {
        return $this->chatAgentFactory
            ->createContextAgent(null, $this->gitRepoProvider)
            ->execute($ctx->getContextConversation());
    }
}