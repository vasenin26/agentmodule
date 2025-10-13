<?php

use Anymodule\Agentmodule\Interface\ActionsFactoryInterface;
use Anymodule\Agentmodule\Interface\ChatAgentFactoryInterface;
use Anymodule\Agentmodule\Interface\ChatSummaryGeneratorInterface;
use Anymodule\Agentmodule\Interface\ConversationCompressorInterface;
use Anymodule\Agentmodule\Interface\ConversationFactoryInterface;
use Anymodule\Agentmodule\Interface\Git\GitRepoProviderInterface;
use Anymodule\Agentmodule\Interface\Page\PageApi;
use Anymodule\Agentmodule\Interface\Page\PageContextServiceFactoryInterface;
use Anymodule\Agentmodule\Interface\Task\TaskApiInterface;
use Anymodule\Agentmodule\Interface\Task\TaskProcessorFactoryInterface;
use Anymodule\Agentmodule\Interface\TaskStorageProviderInterface;
use Anymodule\Agentmodule\Interface\Tools\ToolServiceFactoryInterface;
use DI\ContainerBuilder;
use Anymodule\Agentmodule\Factory\{
    ActionsFactory,
    ConversationFactory,
    LLMFactory,
    PageContextProviderFactory,
    TaskProcessorFactory,
    ToolServiceFactory
};
use Anymodule\Agentmodule\Services\{
    ApiService\DocModuleApi,
    RepositoryService\RepositoryProvider,
    Summary\SummaryCompressor,
    Summary\SummaryGenerator,
    TaskStorageProvider
};
use Anymodule\Agentmodule\Services\ModelsDirectory\ModelsProvider;
use Anymodule\Agentmodule\Utils\BrokenCompressor;

$builder = new ContainerBuilder();
$builder->addDefinitions([

    DocModuleApi::class => fn() => new DocModuleApi(
        host: getenv('API_HOST'),
        token: getenv('API_TOKEN') ?: getenv('AGENT_TOKEN'),
    ),

    GitRepoProviderInterface::class => fn() => new RepositoryProvider(
        branch: getenv('REPO_BRANCH') ?: 'main',
        reposFolder: getenv('REPOS_FOLDER') ?: 'default'
    ),

    ModelsProvider::class => DI\autowire(ModelsProvider::class),
    ToolServiceFactory::class => DI\autowire(ToolServiceFactory::class),

    SummaryCompressor::class => fn($c) => new SummaryCompressor(
        new SummaryGenerator(
            new LLMFactory(
                $c->get(GitRepoProviderInterface::class),
                new BrokenCompressor(),
                $c->get(ModelsProvider::class)
            ),
            $c->get(ToolServiceFactory::class)
        )
    ),

    TaskProcessorFactoryInterface::class => DI\autowire(TaskProcessorFactory::class),

    ToolServiceFactoryInterface::class => DI\autowire(ToolServiceFactory::class),
    ChatAgentFactoryInterface::class => DI\autowire(LLMFactory::class),
    ConversationFactoryInterface::class => DI\autowire(ConversationFactory::class),
    TaskStorageProviderInterface::class => DI\autowire(TaskStorageProvider::class),
    ActionsFactoryInterface::class => DI\autowire(ActionsFactory::class),
    PageContextServiceFactoryInterface::class => DI\autowire(PageContextProviderFactory::class),

    PageApi::class => fn($c) => $c->get(DocModuleApi::class),
    TaskApiInterface::class => fn($c) => $c->get(DocModuleApi::class),

    ConversationCompressorInterface::class => fn($c) => $c->get(SummaryCompressor::class),
    ChatSummaryGeneratorInterface::class => DI\autowire(SummaryGenerator::class),

]);

return $builder->build();
