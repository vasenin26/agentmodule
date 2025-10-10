<?php

use Anymodule\Agentmodule\Interface\Git\GitRepoProviderInterface;
use Anymodule\Agentmodule\Interface\Task\TaskApi;
use Anymodule\Agentmodule\Interface\Task\TaskProcessorFactoryInterface;
use DI\ContainerBuilder;
use Anymodule\Agentmodule\Factory\{
    ActionsFactory, ConversationFactory, LLMFactory, PageContextProviderFactory,
    TaskProcessorFactory, ToolServiceFactory
};
use Anymodule\Agentmodule\Services\{
    ApiService\Service, RepositoryService\RepositoryProvider,
    Summary\SummaryCompressor, Summary\SummaryGenerator, TaskStorageProvider
};
use Anymodule\Agentmodule\Services\ModelsDirectory\ModelsProvider;
use Anymodule\Agentmodule\Utils\BrokenCompressor;

$builder = new ContainerBuilder();
$builder->addDefinitions([

    Service::class => fn() => new Service(
        host: getenv('API_HOST'),
        token: getenv('API_TOKEN') ?: getenv('AGENT_TOKEN'),
    ),

    GitRepoProviderInterface::class => fn() => new RepositoryProvider(
        branch: getenv('REPO_BRANCH') ?: 'main',
        reposFolder: getenv('REPOS_FOLDER') ?: 'default'
    ),

    ModelsProvider::class => fn() => new ModelsProvider(),

    ToolServiceFactory::class => fn($c) => new ToolServiceFactory(
        $c->get(GitRepoProviderInterface::class),
        new PageContextProviderFactory($c->get(Service::class)),
    ),

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

    LLMFactory::class => fn($c) => new LLMFactory(
        $c->get(GitRepoProviderInterface::class),
        $c->get(SummaryCompressor::class),
        $c->get(ModelsProvider::class)
    ),

    TaskProcessorFactoryInterface::class => fn($c) => new TaskProcessorFactory(
        $c->get(ToolServiceFactory::class),
        $c->get(LLMFactory::class),
        new ConversationFactory(),
        new TaskStorageProvider(),
        new ActionsFactory($c->get(ToolServiceFactory::class), $c->get(LLMFactory::class)),
    ),

    TaskApi::class => fn($c) => $c->get(Service::class),

]);

return $builder->build();
