<?php

use Anymodule\Agentmodule\Factory\LLMFactory;
use Anymodule\Agentmodule\Factory\PageContextProviderFactory;
use Anymodule\Agentmodule\Factory\TaskProcessorFactory;
use Anymodule\Agentmodule\Factory\ToolServiceFactory;
use Anymodule\Agentmodule\Runner;
use Anymodule\Agentmodule\Services\ApiService\Service;
use Anymodule\Agentmodule\Services\RepositoryService\RepositoryProvider;
use Anymodule\Agentmodule\Services\ToolsService\ToolsFactory;
use Vasenin26\Conversation\Factory\ConversationFactory;

require __DIR__ . '/vendor/autoload.php';

$api = new Service(
    host: getenv('API_HOST'),
    token: getenv('AGENT_TOKEN'),
);

$processorFactory = new TaskProcessorFactory(
    new ToolServiceFactory(
        new ToolsFactory(
            new RepositoryProvider(),
            new PageContextProviderFactory($api),
        )
    ),
    new LLMFactory(),
    new ConversationFactory(),
);

(new Runner($api, $processorFactory))->run();