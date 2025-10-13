<?php

namespace Anymodule\Agentmodule;

use Anymodule\Agentmodule\Application\ResultHandlers\DocsModule;
use Anymodule\Agentmodule\Interface\StateStoreInterface;
use Anymodule\Agentmodule\Interface\Task\TaskApiInterface;
use Anymodule\Agentmodule\Interface\Task\TaskProcessorFactoryInterface;
use Anymodule\Agentmodule\Utils\Log;
use Ramsey\Uuid\UuidInterface;

final readonly class Runner
{

    const GET_TASK_ATTEMPTS = 10;
    const GET_TASK_SLEEP_TIME = 10;
    const GET_TASK_SLEEP_TIME_ON_ERROR = 3;

    const STORE_AGENT_STATUS_KEY = 'status';
    const STORE_AGENT_ATTEMPT_KEY = 'attempt';

    public function __construct(
        private TaskApiInterface              $api,
        private StateStoreInterface           $stateStore,
        private TaskProcessorFactoryInterface $processorFactory,
    )
    {
    }

    public function run(UuidInterface $agentId): void
    {
        $attemptLimit = self::GET_TASK_ATTEMPTS;

        $this->stateStore->push(self::STORE_AGENT_STATUS_KEY, 'started');

        Log::info("Running agent $agentId module...");

        while (true) {
            try {
                Log::info("Getting task..");
                
                $this->stateStore->push(self::STORE_AGENT_STATUS_KEY, 'getting');
                $this->stateStore->push(self::STORE_AGENT_ATTEMPT_KEY, $attemptLimit);
                $task = $this->api->getTask($agentId);

                if (is_null($task)) {
                    Log::info("Not found task, sleeping 10 seconds...");
                    $this->stateStore->push(self::STORE_AGENT_STATUS_KEY, 'waiting');
                    sleep(self::GET_TASK_SLEEP_TIME);
                    continue;
                }

                Log::info("Processing task: {$task->id}");

                $this->stateStore->push(self::STORE_AGENT_STATUS_KEY, 'processing');

                $handler = new DocsModule($this->api, $agentId, $task);
                $this->processorFactory->createProcessorForTask($task)
                    ->process($task, $handler);

                $attemptLimit = self::GET_TASK_ATTEMPTS;
            } catch (\Throwable $e) {
                Log::exception($e, 'Task processing error', [
                    'agent_id' => (string)$agentId,
                    'task_id' => $task?->id ?? 'unknown',
                    'attempts_remaining' => $attemptLimit - 1,
                ]);
                $this->stateStore->push(self::STORE_AGENT_STATUS_KEY, 'error');
                sleep(self::GET_TASK_SLEEP_TIME_ON_ERROR);
            }

            $attemptLimit--;
            if ($attemptLimit === 0) {
                Log::info("Attempt limit reached, exiting...");
                $this->stateStore->push(self::STORE_AGENT_STATUS_KEY, 'stopped');
                exit(0);
            }
        }
    }
}