<?php

namespace Anymodule\Agentmodule\Application\Workflows;

use Anymodule\Agentmodule\Application\Workflows\Interface\WorkflowWorker;
use Anymodule\Agentmodule\Application\Workflows\Steps\Developer;
use Anymodule\Agentmodule\Application\Workflows\Steps\Planner;
use Anymodule\Agentmodule\Application\Workflows\Steps\Tester;
use Anymodule\Agentmodule\Entity\Task;
use Anymodule\Agentmodule\Interface\ProcessHandlerInterface;
use Anymodule\Agentmodule\Interface\Task\TaskProcessor;

class CodeWorkflow implements TaskProcessor
{
    private $workflow = [
        Planner::class => [
            AbstractWorkflow::SUCCESS => Developer::class,
        ],
        Developer::class => [
            AbstractWorkflow::SUCCESS => Tester::class,
        ],
        Tester::class => [
            AbstractWorkflow::FAILURE => Developer::class,
        ]
    ];

    public function __construct(private WorkflowWorker $worker)
    {
    }

    public function supports(Task $task): bool
    {
        return $task->type == 'code';
    }

    public function process(Task $task, ProcessHandlerInterface $processHandler): void
    {
        $this->worker->process($task, $this->workflow, $processHandler);
    }
}