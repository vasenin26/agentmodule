<?php

namespace Anymodule\Agentmodule\Factory;

use Anymodule\Agentmodule\Interface\ActionContract;
use Anymodule\Agentmodule\Interface\ChatAgentFactoryInterface;
use Anymodule\Agentmodule\Interface\ChatProcessorFactoryInterface;
use Anymodule\Agentmodule\Interface\ConversationCompressorInterface;
use Anymodule\Agentmodule\Interface\Git\GitRepoProviderInterface;
use Anymodule\Agentmodule\Interface\Tools\ToolsProviderInterface;
use Anymodule\Agentmodule\Services\ChatAgent\ChatAgent;
use Anymodule\Agentmodule\Services\ChatGPTMapper\ChatMapper;
use Anymodule\Agentmodule\Services\ModelsDirectory\ModelsProvider;
use Anymodule\Agentmodule\Services\OpenAIChat\ChatProcessor;
use OpenAI;

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