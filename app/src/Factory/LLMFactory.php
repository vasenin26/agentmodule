<?php

namespace Anymodule\Agentmodule\Factory;

use Anymodule\Agentmodule\Interface\ActionContract;
use Anymodule\Agentmodule\Interface\ChatAgentFactoryInterface;
use Anymodule\Agentmodule\Interface\Git\GitRepoProviderInterface;
use Anymodule\Agentmodule\Interface\GPTProcessorInterface;
use Anymodule\Agentmodule\Interface\LLMFactoryInterface;
use Anymodule\Agentmodule\Interface\Tools\LLMTools;
use Anymodule\Agentmodule\Services\ChatAgent\ChatAgent;
use Anymodule\Agentmodule\Services\ChatGPTMapper\ChatMapper;
use Anymodule\Agentmodule\Services\LLMGenerator\LMStudioClient;
use Anymodule\Agentmodule\Services\OpenAIChat\ChatProcessor;
use Anymodule\Agentmodule\Services\ToolsService\ToolsService;

final readonly class LLMFactory implements LLMFactoryInterface, ChatAgentFactoryInterface
{
    public function __construct(
        private GitRepoProviderInterface $repoProvider,
    )
    {
    }

    public function createChat(ToolsService $toolsService): GPTProcessorInterface
    {
        $apiKey = getenv('OPENAI_API_KEY');
        $model = getenv('OPENAI_MODEL');

        return new LMStudioClient(
            $apiKey,
            $model,
            $toolsService,
            new ChatMapper($this->repoProvider)
        );
    }

    public function createAgent(LLMTools $tools): ActionContract
    {
        $processor = new ChatProcessor(
            getenv('OPENAI_API_KEY'),
            getenv('OPENAI_MODEL'),
            new \Anymodule\Agentmodule\Services\ChatGPTMapper\ChatMapper(
                $this->repoProvider
            )
        );

        return new ChatAgent($processor, $tools);
    }
}