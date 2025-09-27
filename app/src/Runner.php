<?php

namespace Anymodule\Agentmodule;

use Anymodule\Agentmodule\Interface\Task\TaskApi;
use Anymodule\Agentmodule\Interface\Task\TaskProcessorFactoryInterface;
use Anymodule\Agentmodule\ResultHandlers\DocsModule;
use Anymodule\Agentmodule\Utils\Log;
use Ramsey\Uuid\Uuid;

final readonly class Runner
{
    public function __construct(
        private TaskApi                       $api,
        private TaskProcessorFactoryInterface $processorFactory,
    )
    {
    }

    public function run(): void
    {
        $agentId = Uuid::fromString("9d00a734-865c-4032-9915-0ad86d2204d7");// Uuid::uuid4();

        Log::info("Running agent $agentId module...");

        while (true) {
            try {
                Log::info("Getting task..");
                $task = $this->api->getTask($agentId);

                if (is_null($task)) {
                    Log::info("Not found task, sleeping 5 seconds...");
                    sleep(5);
                    continue;
                }

                Log::info("Processing task: {$task->id}");

                $handler = new DocsModule($this->api, $agentId, $task);
                $this->processorFactory->createProcessorForTask($task)
                    ->process($task, $handler);
            } catch (\Throwable $e) {
                Log::warning($e->getMessage());
            }
        }
    }
}