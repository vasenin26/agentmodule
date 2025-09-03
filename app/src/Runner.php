<?php

namespace Anymodule\Agentmodule;

use Anymodule\Agentmodule\Interface\TaskApi;
use Anymodule\Agentmodule\Interface\TaskProcessorFactoryInterface;
use Anymodule\Agentmodule\Utils\Log;

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
        Log::info("Running agent module...");

        while (true) {
            Log::info("Getting task..");
            $task = $this->api->getTask();

            if(is_null($task)) {
                Log::info("Not found task, sleeping 5 seconds...");
                sleep(5);
                continue;
            }

            Log::info("Processing task: {$task->id}");

            $this->processorFactory->createProcessorForTask($task)
                ->process($task);
        }
    }
}