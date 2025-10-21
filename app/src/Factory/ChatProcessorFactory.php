<?php

namespace Anymodule\Agentmodule\Factory;

use Anymodule\Agentmodule\Application\ChatAgent\Interface\ChatProcessorInterface;
use Anymodule\Agentmodule\Interface\ChatProcessorFactoryInterface;
use Anymodule\Agentmodule\Interface\ContextConversationProcessorInterface;
use Anymodule\Agentmodule\Interface\Git\GitRepoProviderInterface;
use Anymodule\Agentmodule\Interface\Tools\ToolsProviderInterface;
use Anymodule\Agentmodule\Services\ChatGPTMapper\ChatContextMapper;
use Anymodule\Agentmodule\Services\ChatGPTMapper\ChatMapper;
use Anymodule\Agentmodule\Services\ChatGPTMapper\Interface\OpenAIMessageProcessorInterface;
use Anymodule\Agentmodule\Services\ModelsDirectory\ModelsProvider;
use Anymodule\Agentmodule\Services\OpenAIChat\ChatProcessor;
use Anymodule\Agentmodule\Services\OpenAIChat\ContextConversationProcessor;
use OpenAI;

class ChatProcessorFactory implements ChatProcessorFactoryInterface
{
    public function __construct(
        private OpenAIMessageProcessorInterface $openAIMessageProcessor,
        private ModelsProvider                  $modelsProvider,
        private GitRepoProviderInterface        $repoProvider,
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
                $this->openAIMessageProcessor,
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
                $this->openAIMessageProcessor,
                $this->repoProvider,
                null
            )
        );
    }

    public function createContextProcessor(ToolsProviderInterface $tools): ContextConversationProcessorInterface
    {
        $apiHost = getenv('OPENAI_API_HOST');
        $apiKey = getenv('OPENAI_API_KEY');

        $modelMeta = $this->modelsProvider->get('summary');

        $client = OpenAI::factory()
            ->withApiKey($apiKey)
            ->withBaseUri($apiHost)
            ->withHttpClient(new \GuzzleHttp\Client(['timeout' => 0]))
            ->make();

        return new ContextConversationProcessor(
            $client,
            $modelMeta,
            new ChatContextMapper(
                $this->openAIMessageProcessor,
                new ChatMapper(
                    $this->openAIMessageProcessor,
                    $this->repoProvider,
                    null
                )
            )
        );
    }
}