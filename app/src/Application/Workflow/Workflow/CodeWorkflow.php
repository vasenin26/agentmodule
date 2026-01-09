<?php

namespace Anymodule\Agentmodule\Application\Workflow\Workflow;

use Anymodule\Agentmodule\Application\Context\CodeContext;
use Anymodule\Agentmodule\Application\Workflow\Nodes\Developer;
use Anymodule\Agentmodule\Application\Workflow\Nodes\DoAnswer;
use Anymodule\Agentmodule\Application\Workflow\Nodes\CodePlanner;
use Anymodule\Agentmodule\Application\Workflow\Nodes\Tester;
use Anymodule\Agentmodule\Application\Workflow\Nodes\WaitMessage;
use Anymodule\Agentmodule\Entity\Task;
use Anymodule\Agentmodule\Interface\ProcessHandlerInterface;
use Anymodule\Agentmodule\Interface\Task\TaskProcessor;
use Anymodule\Agentmodule\Services\Workflows\Interface\WorkflowWorker;

class CodeWorkflow implements TaskProcessor
{
    private $workflow = [];

    public function __construct(private WorkflowWorker $worker)
    {
        $this->workflow = [
            CodePlanner::class => function(CodeContext $ctx) {
                if($ctx->hasPlane()) return Developer::class;
                return CodePlanner::class;
            },
            Developer::class => function(CodeContext $ctx) {
                if($ctx->codeFinished()) return Tester::class;
                return Developer::class;
            },
            Tester::class  => function(CodeContext $ctx) {
                if($ctx->testFailed()) return Developer::class;
                if($ctx->testSucceed()) return WaitMessage::class;
                return Tester::class;
            },
            WaitMessage::class => function (CodeContext $ctx) {
                if($ctx->hasMessage()) return DoAnswer::class;
                return WaitMessage::class;
            },
        ];
    }

    public function supports(Task $task): bool
    {
        return $task->type == 'code';
    }

    public function process(Task $task, ProcessHandlerInterface $processHandler): void
    {
        $ctx = new CodeContext($task);
        $this->worker->process($ctx, $this->workflow, $processHandler);
    }
}