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
            ->withHttpClient(new \GuzzleHttp\Client(['timeout' => 0]))
            ->make();

        return new ContextConversationProcessor(
            $client,
            $modelMeta,
            new ChatContextMapper(
                $this->openAIMessageMapper,
                new ChatMapper(
                    $this->openAIMessageMapper,
                    $repositoryProvider,
                    null
                )
            )
        );
    }

    public function createModelContextProcessor(?string $modelName, ToolsProviderInterface $tools, GitRepoProviderInterface $repositoryProvider): ContextConversationProcessorInterface
    {
        if ($modelName === null) {
            $modelName = getenv('OPENAI_MODEL');
        }

        $apiHost = getenv('OPENAI_API_HOST');
        $apiKey = getenv('OPENAI_API_KEY');

        $client = OpenAI::factory()
            ->withApiKey($apiKey)
            ->withBaseUri($apiHost)
            ->withHttpClient(new \GuzzleHttp\Client(['timeout' => 0]))
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
                    null
                )
            )
        );
    }
}