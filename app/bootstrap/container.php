<?php

use Anymodule\Agentmodule\Factory\{ActionsFactory,
    ChatAgentFactory,
    ChatProcessorFactory,
    ConversationFactory,
    PageContextProviderFactory,
    TaskProcessorFactory,
    ToolServiceFactory};
use Anymodule\Agentmodule\Interface\ActionsFactoryInterface;
use Anymodule\Agentmodule\Interface\ChatAgentFactoryInterface;
use Anymodule\Agentmodule\Interface\ChatProcessorFactoryInterface;
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
use Anymodule\Agentmodule\Services\{ApiService\DocModuleApi,
    RepositoryService\RepositoryProvider,
    Summary\SummaryCompressor,
    Summary\SummaryGenerator};
use Anymodule\Agentmodule\Services\ModelsDirectory\ModelsProvider;
use Anymodule\Agentmodule\Services\TaskStorageProvider;
use Anymodule\Agentmodule\Utils\BrokenCompressor;
use DI\ContainerBuilder;

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
            new ChatAgentFactory(
                $c->get(ChatProcessorFactoryInterface::class),
                new BrokenCompressor(),
            ),
            $c->get(ToolServiceFactory::class)
        )
    ),

    ActionsFactoryInterface::class => DI\autowire(ActionsFactory::class),
    ChatAgentFactoryInterface::class => DI\autowire(ChatAgentFactory::class),
    ChatProcessorFactoryInterface::class => DI\autowire(ChatProcessorFactory::class),
    ConversationFactoryInterface::class => DI\autowire(ConversationFactory::class),
    PageContextServiceFactoryInterface::class => DI\autowire(PageContextProviderFactory::class),
    TaskProcessorFactoryInterface::class => DI\autowire(TaskProcessorFactory::class),
    ToolServiceFactoryInterface::class => DI\autowire(ToolServiceFactory::class),

    PageApi::class => fn($c) => $c->get(DocModuleApi::class),
    TaskApiInterface::class => fn($c) => $c->get(DocModuleApi::class),

    ChatSummaryGeneratorInterface::class => DI\autowire(SummaryGenerator::class),
    ConversationCompressorInterface::class => fn($c) => $c->get(SummaryCompressor::class),
    TaskStorageProviderInterface::class => DI\autowire(TaskStorageProvider::class),

]);

return $builder->build();
