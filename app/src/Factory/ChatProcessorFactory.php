<?php

namespace Anymodule\Agentmodule\Factory;

use Anymodule\Agentmodule\Application\ChatAgent\Interface\ChatProcessorInterface;
use Anymodule\Agentmodule\Interface\ChatProcessorFactoryInterface;
use Anymodule\Agentmodule\Interface\Git\GitRepoProviderInterface;
use Anymodule\Agentmodule\Interface\Tools\ToolsProviderInterface;
use Anymodule\Agentmodule\Services\ChatGPTMapper\ChatMapper;
use Anymodule\Agentmodule\Services\ModelsDirectory\ModelsProvider;
use Anymodule\Agentmodule\Services\OpenAIChat\ChatProcessor;
use OpenAI;

class ChatProcessorFactory implements ChatProcessorFactoryInterface
{
    public function __construct(
        private ModelsProvider           $modelsProvider,
        private GitRepoProviderInterface $repoProvider,
    )
    {
    }

    public function createMainProcessor(ToolsProviderInterface $tools): ChatProcessorInterface
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

        return new ChatProcessor(
            $client,
            $modelMeta,
            new ChatMapper(
                $this->repoProvider,
                $tools
            )
        );
    }

    public function createSummaryProcessor(): ChatProcessorInterface
    {
        $apiHost = getenv('OPENAI_API_HOST');
        $apiKey = getenv('OPENAI_API_KEY');

        $modelMeta = $this->modelsProvider->get('summary');

        $client = OpenAI::factory()
            ->withApiKey($apiKey)
            ->withBaseUri($apiHost)
            ->withHttpClient(new \GuzzleHttp\Client(['timeout' => 0]))
            ->make();

        return new ChatProcessor(
            $client,
            $modelMeta,
            new ChatMapper(
                $this->repoProvider,
                null
            )
        );
    }
}