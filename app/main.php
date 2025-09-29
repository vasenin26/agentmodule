<?php

use Anymodule\Agentmodule\Factory\ActionsFactory;
use Anymodule\Agentmodule\Factory\ConversationFactory;
use Anymodule\Agentmodule\Factory\LLMFactory;
use Anymodule\Agentmodule\Factory\PageContextProviderFactory;
use Anymodule\Agentmodule\Factory\TaskProcessorFactory;
use Anymodule\Agentmodule\Factory\ToolServiceFactory;
use Anymodule\Agentmodule\Runner;
use Anymodule\Agentmodule\Services\ApiService\Service;
use Anymodule\Agentmodule\Services\RepositoryService\RepositoryProvider;
use Anymodule\Agentmodule\Services\TaskStorageProvider;

require __DIR__ . '/vendor/autoload.php';

$api = new Service(
    host: getenv('API_HOST'),
    token: getenv('AGENT_TOKEN'),
);

$repoProvider = new RepositoryProvider(reposFolder: 'default', branch: 'main');

$toolFactory = new ToolServiceFactory(
    $repoProvider,
    new PageContextProviderFactory($api),
);

$llmFactory = new LLMFactory($repoProvider);

$processorFactory = new TaskProcessorFactory(
    $toolFactory,
    $llmFactory,
    new ConversationFactory(),
    new TaskStorageProvider(),
    new ActionsFactory($toolFactory, $llmFactory),
);

(new Runner($api, $processorFactory))->run();