<?php

namespace Anymodule\Agentmodule\Factory;

use Anymodule\Agentmodule\Application\ChatAgent\ChatAgent;
use Anymodule\Agentmodule\Interface\ActionContract;
use Anymodule\Agentmodule\Interface\ChatAgentFactoryInterface;
use Anymodule\Agentmodule\Interface\ChatProcessorFactoryInterface;
use Anymodule\Agentmodule\Interface\ConversationCompressorInterface;
use Anymodule\Agentmodule\Interface\Tools\ToolsProviderInterface;

final readonly class ChatAgentFactory implements ChatAgentFactoryInterface
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
}