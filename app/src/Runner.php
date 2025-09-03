<?php

namespace Anymodule\Agentmodule;

use Anymodule\Agentmodule\Interface\TaskApi;
use Anymodule\Agentmodule\Interface\TaskProcessorFactoryInterface;
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
        $agentId = Uuid::uuid4();

        Log::info("Running agent $agentId module...");

        while (true) {
            Log::info("Getting task..");
            $task = $this->api->getTask($agentId);

            if(is_null($task)) {
                Log::info("Not found task, sleeping 5 seconds...");
                sleep(5);
                continue;
            }

            Log::info("Processing task: {$task->id}");

            $result = $this->processorFactory->createProcessorForTask($task)
                ->process($task);

            $this->api->sendResult($agentId, $task->id, $result);
        }
    }
}