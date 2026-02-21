<?php

namespace Anymodule\Agentmodule\Application\TaskProcessor;

use Anymodule\Agentmodule\Application\Context\CodeContext;
use Anymodule\Agentmodule\Application\Tools\Tasks\TaskStorageInterface;
use Anymodule\Agentmodule\Application\Workflow\Nodes\CodePlanner;
use Anymodule\Agentmodule\Application\Workflow\Nodes\Developer;
use Anymodule\Agentmodule\Application\Workflow\Nodes\DoAnswer;
use Anymodule\Agentmodule\Application\Workflow\Nodes\Tester;
use Anymodule\Agentmodule\Application\Workflow\Nodes\WaitMessage;
use Anymodule\Agentmodule\Entity\Context;
use Anymodule\Agentmodule\Entity\ContextConversation;
use Anymodule\Agentmodule\Entity\ProcessingResult;
use Anymodule\Agentmodule\Entity\Task;
use Anymodule\Agentmodule\Interface\Factory\ConversationFactoryInterface;
use Anymodule\Agentmodule\Interface\ProcessHandlerInterface;
use Anymodule\Agentmodule\Interface\Storage\TaskStorageProviderInterface;
use Anymodule\Agentmodule\Interface\Task\TaskProcessor;
use Anymodule\Agentmodule\Services\Workflows\Interface\WorkflowWorker;

final class CodeWorkflow implements TaskProcessor
{
    private array $workflow = [];

    public function __construct(
        readonly private WorkflowWorker               $worker,
        readonly private ConversationFactoryInterface  $conversationFactory,
        readonly private TaskStorageProviderInterface  $taskStorageProvider,
    )
    {
        $this->workflow = [
            'init' => function (CodeContext $ctx) {
                if($ctx->hasMessage()) return DoAnswer::class;
                if($ctx->codeFinished() && !$ctx->testFinished()) return Tester::class;
                return CodePlanner::class;
            },
            DoAnswer::class => function(CodeContext $ctx) {
                if($ctx->hasMessage()) return DoAnswer::class;

                if ($ctx->isDevelopmentRequested()) {
                    $ctx->clearDevelopmentRequest();
                    return $ctx->hasPlane() ? Developer::class : CodePlanner::class;
                }

                if ($ctx->codeFinished() && !$ctx->testFinished()) {
                    return Tester::class;
                }

                return WaitMessage::class;
            },
            CodePlanner::class => function(CodeContext $ctx) {
                if($ctx->hasPlane()) return Developer::class;
                return CodePlanner::class;
            },
            Developer::class => function(CodeContext $ctx) {
                if($ctx->codeFinished()) return Tester::class;
                return Developer::class;
            },
            Tester::class  => function(CodeContext $ctx) {
                if($ctx->testFinished()) {
                    if($ctx->testSucceed()) {
                        return WaitMessage::class;
                    }
                    return Developer::class;
                }
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
        $taskStorage = $this->taskStorageProvider->getTaskStorage($task->id);

        $context = new Context(
            tasks: $taskStorage->list() ?: ($task->context['tasks'] ?? []),
            payload: $task->context['payload'] ?? [],
        );

        $handledConversation = $this->conversationFactory->handledConversation($task->messages, $processHandler);
        $contextConversation = new ContextConversation($context, $handledConversation);
        $ctx = new CodeContext($task, $contextConversation);

        $syncingHandler = new class($processHandler, $context, $taskStorage) implements ProcessHandlerInterface {
            public function __construct(
                private ProcessHandlerInterface $inner,
                private Context                 $context,
                private TaskStorageInterface    $storage,
            ) {}

            public function handle(ProcessingResult $result): void
            {
                $this->context->updateTask($this->storage->list());
                $this->inner->handle($result);
            }
        };

        $this->worker->process($ctx, $this->workflow, $syncingHandler);
    }
}