<?php

namespace Anymodule\Agentmodule\Factory;

use Anymodule\Agentmodule\Application\ChatAgent\ChatAgent;
use Anymodule\Agentmodule\Application\ChatAgent\ContextAgent;
use Anymodule\Agentmodule\Interface\ActionContract;
use Anymodule\Agentmodule\Interface\ChatAgentFactoryInterface;
use Anymodule\Agentmodule\Interface\ChatProcessorFactoryInterface;
use Anymodule\Agentmodule\Interface\ContextActionContract;
use Anymodule\Agentmodule\Interface\ConversationCompressorInterface;
use Anymodule\Agentmodule\Interface\Git\GitRepoProviderInterface;
use Anymodule\Agentmodule\Interface\Tools\ToolsProviderInterface;
use Anymodule\Agentmodule\Services\Summary\Interface\SummaryAgentFactoryInterface;
use Anymodule\Agentmodule\Utils\BrokenCompressor;

final readonly class ChatAgentFactory implements ChatAgentFactoryInterface, SummaryAgentFactoryInterface
{
    public function __construct(
        private ChatProcessorFactoryInterface   $processorFactory,
        private ConversationCompressorInterface $compressor,
    )
    {
    }

    public function createAgent(ToolsProviderInterface $tools): ActionContract
    {
        return new ChatAgent(
            $this->processorFactory->createMainProcessor($tools),
            $this->compressor,
            $tools
        );
    }

    public function createSummaryAgent(): ActionContract
    {
        return new ChatAgent(
            $this->processorFactory->createSummaryProcessor(),
            new BrokenCompressor(),
            null
        );
    }

    public function createContextAgent(ToolsProviderInterface $tools): ContextActionContract
    {
        return new ContextAgent(
            $this->processorFactory->createContextProcessor($tools),
            $this->compressor,
            $tools
        );
    }
}