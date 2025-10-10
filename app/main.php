<?php

use Anymodule\Agentmodule\Interface\Task\TaskApi;
use Anymodule\Agentmodule\Interface\Task\TaskProcessorFactoryInterface;
use Anymodule\Agentmodule\Runner;
use Anymodule\Agentmodule\Services\StateStore;
use Ramsey\Uuid\Uuid;

require __DIR__ . '/vendor/autoload.php';

/**
 * @var $container DI\Container
 */
$container = require __DIR__ . '/bootstrap/container.php';

$agentUuid = getenv('AGENT_UUID');
$agentUuid = $agentUuid ? Uuid::fromString($agentUuid) : Uuid::uuid4();

$runner = new Runner(
    $container->get(TaskApi::class),
    StateStore::run(),
    $container->get(TaskProcessorFactoryInterface::class)
);

$runner->run($agentUuid);