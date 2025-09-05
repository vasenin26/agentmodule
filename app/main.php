<?php

use Anymodule\Agentmodule\Factory\ChatFactory;
use Anymodule\Agentmodule\Factory\PageContextProviderFactory;
use Anymodule\Agentmodule\Factory\TaskProcessorFactory;
use Anymodule\Agentmodule\Factory\ToolServiceFactory;
use Anymodule\Agentmodule\Runner;
use Anymodule\Agentmodule\Services\ApiService\Service;
use Anymodule\Agentmodule\Services\EnvTokenStorage;
use Anymodule\Agentmodule\Services\Git\RepoProvider;
use Anymodule\Agentmodule\Services\RepositoryTokenProvider;
use Anymodule\Agentmodule\Services\ToolsService\ToolsFactory;

require __DIR__ . '/vendor/autoload.php';

$api = new Service(
    host: getenv('API_HOST'),
    token: getenv('AGENT_TOKEN'),
);

$gitTokenProvider = new RepositoryTokenProvider(new EnvTokenStorage());

$processorFactory = new TaskProcessorFactory(
    $gitTokenProvider,
    new ToolServiceFactory(
        new ToolsFactory(
            new RepoProvider(),
            new PageContextProviderFactory($api),
        )
    ),
    new ChatFactory()
);

(new Runner($api, $processorFactory))->run();