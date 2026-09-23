<?php

namespace Anymodule\Agentmodule\Factory;

use Anymodule\Agentmodule\Application\ChatAgent\Interface\ChatProcessorInterface;
use Anymodule\Agentmodule\Application\ChatAgent\Interface\ContextConversationProcessorInterface;
use Anymodule\Agentmodule\Application\Mappers\ChatGPTMapper\ChatContextMapper;
use Anymodule\Agentmodule\Application\Mappers\ChatGPTMapper\ChatMapper;
use Anymodule\Agentmodule\Application\Mappers\ChatGPTMapper\Interface\OpenAIMessageMapperInterface;
use Anymodule\Agentmodule\Application\ModelsDirectory\ModelsProvider;
use Anymodule\Agentmodule\Interface\Factory\ChatProcessorFactoryInterface;
use Anymodule\Agentmodule\Interface\Git\GitRepoProviderInterface;
use Anymodule\Agentmodule\Interface\Tools\ToolsProviderInterface;
use Anymodule\Agentmodule\Services\OpenAIChat\ChatProcessor;
use Anymodule\Agentmodule\Services\OpenAIChat\ContextConversationProcessor;
use OpenAI;

class ChatProcessorFactory implements ChatProcessorFactoryInterface
{
    public function __construct(
        private OpenAIMessageMapperInterface $openAIMessageMapper,
        private ModelsProvider               $modelsProvider,
    )
    {
    }

    public function createContextProcessor(ToolsProviderInterface $tools, GitRepoProviderInterface $repositoryProvider): ContextConversationProcessorInterface
    {
        $apiHost = getenv('OPENAI_API_HOST');
        $apiKey = getenv('OPENAI_API_KEY');

        $modelName = getenv('OPENAI_MODEL');
        $modelMeta = $this->modelsProvider->get($modelName);

//        return new \Anymodule\Agentmodule\Services\StupidJoe\ContextConversationProcessor(
//            $modelMeta,
//            new \Anymodule\Agentmodule\Services\StupidJoe\Service\StupidProcessorService()
//        );

        $client = OpenAI::factory()
            ->withApiKey($apiKey)
            ->withBaseUri($apiHost)
            ->withHttpClient($this->createHttpClient())
            ->make();

        return new ContextConversationProcessor(
            $client,
            $modelMeta,
            new ChatContextMapper(
                $this->openAIMessageMapper,
                new ChatMapper(
                    $this->openAIMessageMapper,
                    $repositoryProvider,
                    $tools
                )
            )
        );
    }

    public function createModelContextProcessor(?string $modelName, ?ToolsProviderInterface $tools, GitRepoProviderInterface $repositoryProvider): ContextConversationProcessorInterface
    {
        if ($modelName === null) {
            $modelName = getenv('OPENAI_MODEL');
        }

        $apiHost = getenv('OPENAI_API_HOST');
        $apiKey = getenv('OPENAI_API_KEY');

        $client = OpenAI::factory()
            ->withApiKey($apiKey)
            ->withBaseUri($apiHost)
            ->withHttpClient($this->createHttpClient())
            ->make();

        $modelMeta = $this->modelsProvider->get($modelName);

        return new ContextConversationProcessor(
            $client,
            $modelMeta,
            new ChatContextMapper(
                $this->openAIMessageMapper,
                new ChatMapper(
                    $this->openAIMessageMapper,
                    $repositoryProvider,
                    $tools
                )
            )
        );
    }

    private function createHttpClient(): \GuzzleHttp\Client
    {
        $options = ['timeout' => 0];

        // HTTP-прокси только для запросов к OpenAI, передаётся agentmanager'ом
        $proxy = getenv('OPENAI_PROXY');
        if ($proxy !== false && $proxy !== '') {
            $options['proxy'] = $proxy;
        }

        return new \GuzzleHttp\Client($options);
    }
}