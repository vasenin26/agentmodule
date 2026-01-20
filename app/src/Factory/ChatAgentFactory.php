<?php

namespace Anymodule\Agentmodule\Factory;

use Anymodule\Agentmodule\Application\ChatAgent\ChatAgent;
use Anymodule\Agentmodule\Application\ChatAgent\ContextAgent;
use Anymodule\Agentmodule\Interface\ActionContract;
use Anymodule\Agentmodule\Interface\ContextActionContract;
use Anymodule\Agentmodule\Interface\ConversationCompressorInterface;
use Anymodule\Agentmodule\Interface\Factory\ChatAgentFactoryInterface;
use Anymodule\Agentmodule\Interface\Factory\ChatProcessorFactoryInterface;
use Anymodule\Agentmodule\Interface\Git\GitRepoProviderInterface;
use Anymodule\Agentmodule\Interface\Tools\ToolsProviderInterface;
use Anymodule\Agentmodule\Services\Summary\Interface\SummaryAgentFactoryInterface;

final readonly class ChatAgentFactory implements ChatAgentFactoryInterface, SummaryAgentFactoryInterface
{
    public function __construct(
        private ChatProcessorFactoryInterface   $processorFactory,
        private ConversationCompressorInterface $compressor,
    )
    {
    }

    public function createAgent(ToolsProviderInterface $tools, GitRepoProviderInterface $repositoryProvider): ActionContract
    {
        return new ChatAgent(
            $this->createModelContextAgent(null, $tools, $repositoryProvider)
        );
    }

    public function createSummaryAgent(GitRepoProviderInterface $repositoryProvider): ActionContract
    {
        return new ChatAgent(
            $this->createModelContextAgent('summary', null, $repositoryProvider)
        );
    }

    public function createContextAgent(?ToolsProviderInterface $tools, GitRepoProviderInterface $repositoryProvider): ContextActionContract
    {
        return new ContextAgent(
            $this->processorFactory->createModelContextProcessor(null, $tools, $repositoryProvider),
            $this->compressor,
            $tools
        );
    }

    public function createModelContextAgent(?string $modelName, ?ToolsProviderInterface $tools, GitRepoProviderInterface $repoProvider): ContextActionContract
    {
        return new ContextAgent(
            $this->processorFactory->createModelContextProcessor($modelName, $tools, $repoProvider),
            $this->compressor,
            $tools
        );
    }
}