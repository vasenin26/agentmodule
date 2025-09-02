<?php

namespace Anymodule\Agentmodule;

use Anymodule\Agentmodule\Interface\TaskApi;
use Anymodule\Agentmodule\Interface\TaskProcessor;
use Anymodule\Agentmodule\Interface\TaskProcessorFactory;

final readonly class Runner
{
    public function __construct(
        private TaskApi $api,
        private TaskProcessorFactory $processorFactory,
    )
    {
    }

    public function run(): void
    {
        while (true) {
            $task = $this->api->getTask();

            if(is_null($task)) {
                sleep(5);
                continue;
            }

            $this->processorFactory->createProcessorForTask($task)
                ->process($task);
        }
    }
}