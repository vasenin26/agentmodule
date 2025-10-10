<?php

namespace Anymodule\Agentmodule\Factory;

use Anymodule\Agentmodule\Interface\ActionContract;
use Anymodule\Agentmodule\Interface\ChatAgentFactoryInterface;
use Anymodule\Agentmodule\Interface\ConversationCompressorInterface;
use Anymodule\Agentmodule\Interface\Git\GitRepoProviderInterface;
use Anymodule\Agentmodule\Interface\GPTProcessorInterface;
use Anymodule\Agentmodule\Interface\LLMFactoryInterface;
use Anymodule\Agentmodule\Interface\Tools\ToolsProvider;
use Anymodule\Agentmodule\Services\ChatAgent\ChatAgent;
use Anymodule\Agentmodule\Services\ChatGPTMapper\ChatMapper;
use Anymodule\Agentmodule\Services\LLMGenerator\LMStudioClient;
use Anymodule\Agentmodule\Services\ModelsDirectory\ModelsProvider;
use Anymodule\Agentmodule\Services\OpenAIChat\ChatProcessor;
use Anymodule\Agentmodule\Services\ToolsService\ToolsProviderService;
use OpenAI;

final readonly class LLMFactory implements LLMFactoryInterface, ChatAgentFactoryInterface
{
    public function __construct(
        private GitRepoProviderInterface        $repoProvider,
        private ConversationCompressorInterface $compressor,
        private ModelsProvider                  $modelsProvider,
    )
    {
    }

    /**
     * @deprecated use agent instead chat, see createAgent method
     */
    public function createChat(ToolsProviderService $toolsService): GPTProcessorInterface
    {
        $apiKey = getenv('OPENAI_API_KEY');
        $model = getenv('OPENAI_MODEL');

        return new LMStudioClient(
            $apiKey,
            $model,
            $toolsService,
            new ChatMapper($this->repoProvider, null, $toolsService)
        );
    }

    public function createAgent(ToolsProvider $tools): ActionContract
    {
        $apiKey = getenv('OPENAI_API_KEY');
        $modelName = getenv('OPENAI_MODEL');

        $modelMeta = $this->modelsProvider->get($modelName);

        $client = OpenAI::factory()
            ->withApiKey($apiKey)
            ->withBaseUri('http://host.docker.internal:1234/v1')
            ->withHttpClient(new \GuzzleHttp\Client(['timeout' => 0]))
            ->make();

        $processor = new ChatProcessor(
            $client,
            $modelMeta,
            new ChatMapper(
                $this->repoProvider,
                null,
                $tools
            )
        );

        return new ChatAgent($processor, $this->compressor, $tools);
    }
}