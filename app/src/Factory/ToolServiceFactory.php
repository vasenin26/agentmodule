<?php

namespace Anymodule\Agentmodule\Factory;


use Anymodule\Agentmodule\Interface\Git\GitRepoProviderInterface;
use Anymodule\Agentmodule\Interface\Page\PageContextServiceFactoryInterface;
use Anymodule\Agentmodule\Interface\Tools\ToolServiceFactoryInterface;
use Anymodule\Agentmodule\Services\RepositoryService\RepositoryProvider;
use Anymodule\Agentmodule\Services\ToolsService\ToolsBuilder;
use Anymodule\Agentmodule\Services\ToolsService\ToolsFactory;
use Anymodule\Agentmodule\Services\ToolsService\ToolsService;
use Anymodule\Agentmodule\Tools\Tasks\TasksStorage;

class ToolServiceFactory implements ToolServiceFactoryInterface
{
    private ToolsFactory $factory;
    private TasksStorage $tasksStorage;

    public function __construct(
        GitRepoProviderInterface $gitRepoProvider,
        private PageContextServiceFactoryInterface $pageContextServiceFactory,
    )
    {
        $this->factory = new ToolsFactory($gitRepoProvider, $pageContextServiceFactory);
    }

    public function withTools(array $tools): ToolsService
    {
        return new ToolsService($this->factory->sendResult(), $tools);
    }

    public function withMainTools(): ToolsService
    {
        return new ToolsService(
            $this->factory->sendResult(),
            [
                // Базовые утилиты
                'time' => $this->factory->time(),
            ]
        );
    }

    public function createToolsBuilder(): ToolsBuilder
    {
        return new ToolsBuilder($this, $this->factory);
    }

    public function createToolsBuilderWithRepository(RepositoryProvider $repositoryProvider): ToolsBuilder
    {
        return new ToolsBuilder($this, new ToolsFactory($repositoryProvider, $this->pageContextServiceFactory));
    }
}
