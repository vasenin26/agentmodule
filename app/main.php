<?php

use Anymodule\Agentmodule\Application\AgentMeta;
use Anymodule\Agentmodule\Interface\AgentMetaProviderInterface;
use Anymodule\Agentmodule\Interface\Task\TaskApiInterface;
use Anymodule\Agentmodule\Interface\Task\TaskProcessorFactoryInterface;
use Anymodule\Agentmodule\Runner;
use Anymodule\Agentmodule\Services\StateStore;
use Ramsey\Uuid\Uuid;

require __DIR__ . '/vendor/autoload.php';

/**
 * @var $container DI\Container
 */
$container = require __DIR__ . '/bootstrap/container.php';

$agentMeta = $container->get(AgentMetaProviderInterface::class);

$runner = new Runner(
    $container->get(TaskApiInterface::class),
    StateStore::run(),
    $container->get(TaskProcessorFactoryInterface::class)
);

$runner->run($agentMeta->getAgentUuid());