<?php

use Anymodule\Agentmodule\Factory\ChatFactory;
use Anymodule\Agentmodule\Factory\PageContextProviderFactory;
use Anymodule\Agentmodule\Factory\TaskProcessorFactory;
use Anymodule\Agentmodule\Factory\ToolServiceFactory;
use Anymodule\Agentmodule\Runner;
use Anymodule\Agentmodule\Services\Git\RepoProvider;
use Anymodule\Agentmodule\Services\ToolsService\ToolsFactory;
use Anymodule\Agentmodule\Utils\FakeApi;

require __DIR__ . '/vendor/autoload.php';

//$api = new ApiService("http://docmodule-development-1:8000/api");
$api = new FakeApi();

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