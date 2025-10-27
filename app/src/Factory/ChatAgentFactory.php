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
use Anymodule\Agentmodule\Interface\Storage\ProjectSettingsInterface;
use Anymodule\Agentmodule\Interface\Tools\ToolsProviderInterface;
use Anymodule\Agentmodule\Services\Summary\Interface\SummaryAgentFactoryInterface;
use Anymodule\Agentmodule\Utils\BrokenCompressor;

final readonly class ChatAgentFactory implements ChatAgentFactoryInterface, SummaryAgentFactoryInterface
{
    public function __construct(
        private ProjectSettingsInterface        $projectSettings,
        private ChatProcessorFactoryInterface   $processorFactory,
        private ConversationCompressorInterface $compressor,
    )
    {
    }

    public function createAgent(ToolsProviderInterface $tools, GitRepoProviderInterface $repositoryProvider): ActionContract
    {
        return new ChatAgent(
            $this->processorFactory->createMainProcessor($tools, $repositoryProvider),
            $this->compressor,
            $tools
        );
    }

    public function createSummaryAgent(GitRepoProviderInterface $repositoryProvider): ActionContract
    {
        return new ChatAgent(
            $this->processorFactory->createSummaryProcessor($repositoryProvider),
            new BrokenCompressor(),
            null
        );
    }

    public function createContextAgent(ToolsProviderInterface $tools, GitRepoProviderInterface $repositoryProvider): ContextActionContract
    {
        return new ContextAgent(
            $this->processorFactory->createContextProcessor($tools, $repositoryProvider),
            $this->compressor,
            $tools
        );
    }

    public function createTaskContextAgent(
        string                   $taskType,
        ToolsProviderInterface   $tools,
        GitRepoProviderInterface $repositoryProvider
    ): ContextActionContract
    {
        $typeModel = $this->projectSettings->getPreferredModel($taskType);

        return new ContextAgent(
            $this->processorFactory->createModelContextProcessor($typeModel, $tools, $repositoryProvider),
            $this->compressor,
            $tools
        );
    }
}