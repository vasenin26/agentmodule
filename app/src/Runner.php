<?php

namespace Anymodule\Agentmodule;

use Anymodule\Agentmodule\Interface\Task\TaskApi;
use Anymodule\Agentmodule\Interface\Task\TaskProcessorFactoryInterface;
use Anymodule\Agentmodule\ResultHandlers\DocsModule;
use Anymodule\Agentmodule\Utils\Log;
use Ramsey\Uuid\Uuid;

final readonly class Runner
{

    const GET_TASK_ATTEMPTS = 10;
    const GET_TASK_SLEEP_TIME = 10;
    const GET_TASK_SLEEP_TIME_ON_ERROR = 3;

    public function __construct(
        private TaskApi                       $api,
        private TaskProcessorFactoryInterface $processorFactory,
    )
    {
    }

    public function run(): void
    {
        $agentId = Uuid::uuid4();
        $attemptLimit = self::GET_TASK_ATTEMPTS;

        Log::info("Running agent $agentId module...");

        while (true) {
            try {
                Log::info("Getting task..");
                $task = $this->api->getTask($agentId);

                if (is_null($task)) {
                    Log::info("Not found task, sleeping 10 seconds...");
                    sleep(self::GET_TASK_SLEEP_TIME);
                    continue;
                }

                Log::info("Processing task: {$task->id}");

                $handler = new DocsModule($this->api, $agentId, $task);
                $this->processorFactory->createProcessorForTask($task)
                    ->process($task, $handler);

                $attemptLimit = self::GET_TASK_ATTEMPTS;
            } catch (\Throwable $e) {
                Log::warning($e->getMessage());
                sleep(self::GET_TASK_SLEEP_TIME_ON_ERROR);
            }

            $attemptLimit--;
            if ($attemptLimit === 0) {
                Log::info("Attempt limit reached, exiting...");
                exit(0);
            }
        }
    }
}