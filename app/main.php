<?php

use Anymodule\Agentmodule\Factory\ChatFactory;
use Anymodule\Agentmodule\Factory\PageContextProviderFactory;
use Anymodule\Agentmodule\Factory\TaskProcessorFactory;
use Anymodule\Agentmodule\Factory\ToolServiceFactory;
use Anymodule\Agentmodule\Runner;
use Anymodule\Agentmodule\Services\ApiService\ApiService;
use Anymodule\Agentmodule\Services\Git\RepoProvider;
use Anymodule\Agentmodule\Services\ToolsService\ToolsFactory;

require __DIR__ . '/vendor/autoload.php';

$api = new ApiService(
    host: getenv('API_HOST'),
    token: getenv('AGENT_TOKEN'),
);

$processorFactory = new TaskProcessorFactory(
    new ToolServiceFactory(
        new ToolsFactory(
            new RepoProvider(),
            new PageContextProviderFactory($api),
        )
    ),
    new ChatFactory()
);

(new Runner($api, $processorFactory))->run();