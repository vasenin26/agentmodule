<?php

namespace Anymodule\Agentmodule\Policy\TaskProcessing;

use Anymodule\Agentmodule\Entity\Task;
use Anymodule\Agentmodule\Factory\TaskProcessorFactory;
use Anymodule\Agentmodule\Interface\Task\TaskProcessor;

final class TaskProcessorRouter
{
    public function __construct(
        private TaskProcessorFactory $factory
    ) {}

    public function resolve(Task $task): TaskProcessor
    {
        foreach (TaskProcessorOrder::ordered() as $processorClass) {
            $processor = $this->factory->create($processorClass);

            if ($processor->supports($task)) {
                return $processor;
            }
        }

        throw new \LogicException('No TaskProcessor matched the task');
    }
}
