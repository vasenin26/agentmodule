<?php

namespace Anymodule\Agentmodule\Factory;

use Anymodule\Agentmodule\Interface\ActionContract;
use Anymodule\Agentmodule\Interface\ChatAgentFactoryInterface;
use Anymodule\Agentmodule\Interface\ConversationCompressorInterface;
use Anymodule\Agentmodule\Interface\Git\GitRepoProviderInterface;
use Anymodule\Agentmodule\Interface\Tools\ToolsProviderInterface;
use Anymodule\Agentmodule\Services\ChatAgent\ChatAgent;
use Anymodule\Agentmodule\Services\ChatGPTMapper\ChatMapper;
use Anymodule\Agentmodule\Services\ModelsDirectory\ModelsProvider;
use Anymodule\Agentmodule\Services\OpenAIChat\ChatProcessor;
use OpenAI;

final readonly class LLMFactory implements ChatAgentFactoryInterface
{
    public function __construct(
        private GitRepoProviderInterface        $repoProvider,
        private ConversationCompressorInterface $compressor,
        private ModelsProvider                  $modelsProvider,
    )
    {
    }

    public function createAgent(ToolsProviderInterface $tools): ActionContract
    {
        $apiHost = getenv('OPENAI_API_HOST');
        $apiKey = getenv('OPENAI_API_KEY');
        $modelName = getenv('OPENAI_MODEL');

        $modelMeta = $this->modelsProvider->get($modelName);

        $client = OpenAI::factory()
            ->withApiKey($apiKey)
            ->withBaseUri($apiHost)
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