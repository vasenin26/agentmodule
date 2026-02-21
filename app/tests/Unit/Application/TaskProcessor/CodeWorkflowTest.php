<?php

namespace Anymodule\Agentmodule\Tests\Unit\Application\TaskProcessor;

use Anymodule\Agentmodule\Application\Context\CodeContext;
use Anymodule\Agentmodule\Application\Conversation\HandledConversation;
use Anymodule\Agentmodule\Application\TaskProcessor\CodeWorkflow;
use Anymodule\Agentmodule\Application\Workflow\Nodes\CodePlanner;
use Anymodule\Agentmodule\Application\Workflow\Nodes\Developer;
use Anymodule\Agentmodule\Application\Workflow\Nodes\DoAnswer;
use Anymodule\Agentmodule\Application\Workflow\Nodes\Tester;
use Anymodule\Agentmodule\Application\Workflow\Nodes\WaitMessage;
use Anymodule\Agentmodule\Entity\Context;
use Anymodule\Agentmodule\Entity\ContextConversation;
use Anymodule\Agentmodule\Entity\Task;
use Anymodule\Agentmodule\Application\Tools\Tasks\TaskStorageInterface;
use Anymodule\Agentmodule\Interface\Factory\ConversationFactoryInterface;
use Anymodule\Agentmodule\Interface\ProcessHandlerInterface;
use Anymodule\Agentmodule\Interface\Storage\TaskStorageProviderInterface;
use Anymodule\Agentmodule\Services\Workflows\Interface\WorkflowWorker;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Vasenin26\Conversation\Chat;
use Vasenin26\Conversation\Interface\Conversation;

class CodeWorkflowTest extends TestCase
{
    private WorkflowWorker|MockObject $worker;
    private ConversationFactoryInterface|MockObject $conversationFactory;
    private TaskStorageProviderInterface|MockObject $taskStorageProvider;
    private CodeWorkflow $processor;

    protected function setUp(): void
    {
        $this->worker = $this->createMock(WorkflowWorker::class);
        $this->conversationFactory = $this->createMock(ConversationFactoryInterface::class);

        $taskStorage = $this->createMock(TaskStorageInterface::class);
        $taskStorage->method('list')->willReturn([]);

        $this->taskStorageProvider = $this->createMock(TaskStorageProviderInterface::class);
        $this->taskStorageProvider->method('getTaskStorage')->willReturn($taskStorage);

        $this->processor = new CodeWorkflow($this->worker, $this->conversationFactory, $this->taskStorageProvider);
    }

    public function testSupportsWithCodeTask(): void
    {
        $task = new Task(
            id: 1,
            type: 'code',
            conversationId: 1,
            messages: [],
            context: [],
            projectId: 1,
            resultRequired: false,
        );

        $this->assertTrue($this->processor->supports($task));
    }

    public function testSupportsWithNonCodeTask(): void
    {
        $task = new Task(
            id: 1,
            type: 'terminal',
            conversationId: 1,
            messages: [],
            context: [],
            projectId: 1,
            resultRequired: false,
        );

        $this->assertFalse($this->processor->supports($task));
    }

    public function testProcessCallsWorkerWithCorrectParameters(): void
    {
        $task = new Task(
            id: 1,
            type: 'code',
            conversationId: 1,
            messages: [['role' => 'user', 'content' => 'Test message']],
            context: ['tasks' => [['id' => 1, 'title' => 'Test task', 'done' => false]]],
            projectId: 1,
            resultRequired: false,
        );

        $processHandler = $this->createMock(ProcessHandlerInterface::class);
        $conversation = new Chat();
        $handledConversation = new HandledConversation($conversation, $processHandler);

        $this->conversationFactory->expects($this->once())
            ->method('handledConversation')
            ->with($task->messages, $processHandler)
            ->willReturn($handledConversation);

        $this->worker->expects($this->once())
            ->method('process')
            ->with(
                $this->callback(function (CodeContext $ctx) use ($task) {
                    return $ctx->getTask() === $task
                        && $ctx->getProjectId() === $task->projectId;
                }),
                $this->callback(function (array $workflow) {
                    return array_key_exists(CodePlanner::class, $workflow)
                        && array_key_exists(Developer::class, $workflow)
                        && array_key_exists(Tester::class, $workflow)
                        && array_key_exists(WaitMessage::class, $workflow);
                })
            );

        $this->processor->process($task, $processHandler);
    }

    public function testProcessCreatesContextConversationCorrectly(): void
    {
        $task = new Task(
            id: 1,
            type: 'code',
            conversationId: 1,
            messages: [['role' => 'user', 'content' => 'Test']],
            context: ['tasks' => [['id' => 1, 'title' => 'Task 1', 'done' => false]]],
            projectId: 1,
            resultRequired: false,
        );

        $processHandler = $this->createMock(ProcessHandlerInterface::class);
        $conversation = new Chat();
        $handledConversation = new HandledConversation($conversation, $processHandler);

        $this->conversationFactory->expects($this->once())
            ->method('handledConversation')
            ->with($task->messages, $processHandler)
            ->willReturn($handledConversation);

        $this->worker->expects($this->once())
            ->method('process')
            ->with(
                $this->callback(function (CodeContext $ctx) use ($task, $handledConversation) {
                    $contextConversation = $ctx->getContextConversation();
                    return $contextConversation instanceof ContextConversation
                        && $contextConversation->context->tasks === $task->context['tasks']
                        && $contextConversation->conversation === $handledConversation;
                }),
                $this->anything()
            );

        $this->processor->process($task, $processHandler);
    }

    public function testProcessWithEmptyContext(): void
    {
        $task = new Task(
            id: 1,
            type: 'code',
            conversationId: 1,
            messages: [],
            context: [],
            projectId: 1,
            resultRequired: false,
        );

        $processHandler = $this->createMock(ProcessHandlerInterface::class);
        $conversation = new Chat();
        $handledConversation = new HandledConversation($conversation, $processHandler);

        $this->conversationFactory->expects($this->once())
            ->method('handledConversation')
            ->with([], $processHandler)
            ->willReturn($handledConversation);

        $this->worker->expects($this->once())
            ->method('process')
            ->with(
                $this->callback(function (CodeContext $ctx) {
                    $contextConversation = $ctx->getContextConversation();
                    return $contextConversation->context->tasks === [];
                }),
                $this->anything()
            );

        $this->processor->process($task, $processHandler);
    }

    public function testWorkflowStructure(): void
    {
        $task = new Task(
            id: 1,
            type: 'code',
            conversationId: 1,
            messages: [],
            context: [],
            projectId: 1,
            resultRequired: false,
        );

        $processHandler = $this->createMock(ProcessHandlerInterface::class);
        $conversation = new Chat();
        $handledConversation = new HandledConversation($conversation, $processHandler);

        $this->conversationFactory->method('handledConversation')->willReturn($handledConversation);

        $this->worker->expects($this->once())
            ->method('process')
            ->with(
                $this->anything(),
                $this->callback(function (array $workflow) {
                    // Проверяем, что workflow содержит все необходимые узлы
                    $this->assertArrayHasKey(CodePlanner::class, $workflow);
                    $this->assertArrayHasKey(Developer::class, $workflow);
                    $this->assertArrayHasKey(Tester::class, $workflow);
                    $this->assertArrayHasKey(WaitMessage::class, $workflow);

                    // Rules are callables; WaitMessage is dead-end (null)
                    $this->assertIsCallable($workflow[CodePlanner::class]);
                    $this->assertIsCallable($workflow[Developer::class]);
                    $this->assertIsCallable($workflow[Tester::class]);
                    $this->assertNull($workflow[WaitMessage::class]);

                    return true;
                })
            );

        $this->processor->process($task, $processHandler);
    }

    public function testWorkflowCodePlannerToDeveloperTransition(): void
    {
        $task = new Task(
            id: 1,
            type: 'code',
            conversationId: 1,
            messages: [],
            context: [],
            projectId: 1,
            resultRequired: false,
        );

        $processHandler = $this->createMock(ProcessHandlerInterface::class);
        $conversation = new Chat();
        $handledConversation = new HandledConversation($conversation, $processHandler);

        $this->conversationFactory->method('handledConversation')->willReturn($handledConversation);

        $workflowCaptured = null;
        $this->worker->expects($this->once())
            ->method('process')
            ->willReturnCallback(function ($ctx, $workflow) use (&$workflowCaptured) {
                $workflowCaptured = $workflow;
            });

        $this->processor->process($task, $processHandler);

        // Проверяем переходы после вызова process
        $this->assertNotNull($workflowCaptured);
        
        // Создаем реальный контекст для проверки переходов
        // Важно: state теперь хранится в Context->payload, поэтому разные CodeContext,
        // построенные поверх одного ContextConversation, будут делить state.
        $contextWithPlane = new Context([]);
        $contextConversationWithPlane = new ContextConversation($contextWithPlane, $conversation);
        $ctxWithPlane = new CodeContext($task, $contextConversationWithPlane);
        $ctxWithPlane->setPlane([['id' => 1, 'title' => 'Task']]);
        
        $contextWithoutPlane = new Context([]);
        $contextConversationWithoutPlane = new ContextConversation($contextWithoutPlane, $conversation);
        $ctxWithoutPlane = new CodeContext($task, $contextConversationWithoutPlane);

        // Проверяем переход CodePlanner -> Developer когда есть план
        $nextNode = $workflowCaptured[CodePlanner::class]($ctxWithPlane);
        $this->assertEquals(Developer::class, $nextNode);

        // Проверяем, что CodePlanner остается на месте когда нет плана
        $nextNode = $workflowCaptured[CodePlanner::class]($ctxWithoutPlane);
        $this->assertEquals(CodePlanner::class, $nextNode);
    }

    public function testWorkflowDeveloperToTesterTransition(): void
    {
        $task = new Task(
            id: 1,
            type: 'code',
            conversationId: 1,
            messages: [],
            context: [],
            projectId: 1,
            resultRequired: false,
        );

        $processHandler = $this->createMock(ProcessHandlerInterface::class);
        $conversation = new Chat();
        $handledConversation = new HandledConversation($conversation, $processHandler);

        $this->conversationFactory->method('handledConversation')->willReturn($handledConversation);

        $workflowCaptured = null;
        $this->worker->expects($this->once())
            ->method('process')
            ->willReturnCallback(function ($ctx, $workflow) use (&$workflowCaptured) {
                $workflowCaptured = $workflow;
            });

        $this->processor->process($task, $processHandler);

        $this->assertNotNull($workflowCaptured);

        $contextUntestedDev = new Context([]);
        $contextConversationUntestedDev = new ContextConversation($contextUntestedDev, $conversation);
        $ctxUntestedDev = new CodeContext($task, $contextConversationUntestedDev);
        $ctxUntestedDev->incrementDevRound();
        $nextNode = $workflowCaptured[Developer::class]($ctxUntestedDev);
        $this->assertEquals(Tester::class, $nextNode, 'Developer → Tester when testedRound < devRound');

        $contextNoUntested = new Context([]);
        $contextConversationNoUntested = new ContextConversation($contextNoUntested, $conversation);
        $ctxNoUntested = new CodeContext($task, $contextConversationNoUntested);
        $nextNode = $workflowCaptured[Developer::class]($ctxNoUntested);
        $this->assertEquals(Developer::class, $nextNode, 'Developer stays when testedRound >= devRound');
    }

    public function testWorkflowTesterToWaitMessageOnSuccess(): void
    {
        $task = new Task(
            id: 1,
            type: 'code',
            conversationId: 1,
            messages: [],
            context: [],
            projectId: 1,
            resultRequired: false,
        );

        $processHandler = $this->createMock(ProcessHandlerInterface::class);
        $conversation = new Chat();
        $handledConversation = new HandledConversation($conversation, $processHandler);

        $this->conversationFactory->method('handledConversation')->willReturn($handledConversation);

        $this->worker->expects($this->once())
            ->method('process')
            ->with(
                $this->anything(),
                $this->callback(function (array $workflow) {
                    $ctx = $this->createMock(CodeContext::class);
                    $ctx->method('testedRound')->willReturn(1);
                    $ctx->method('devRound')->willReturn(1);
                    $ctx->method('lastTestResult')->willReturn(true);
                    $nextNode = $workflow[Tester::class]($ctx);
                    $this->assertEquals(WaitMessage::class, $nextNode);

                    return true;
                })
            );

        $this->processor->process($task, $processHandler);
    }

    public function testWorkflowTesterToDeveloperOnFailure(): void
    {
        $task = new Task(
            id: 1,
            type: 'code',
            conversationId: 1,
            messages: [],
            context: [],
            projectId: 1,
            resultRequired: false,
        );

        $processHandler = $this->createMock(ProcessHandlerInterface::class);
        $conversation = new Chat();
        $handledConversation = new HandledConversation($conversation, $processHandler);

        $this->conversationFactory->method('handledConversation')->willReturn($handledConversation);

        $workflowCaptured = null;
        $this->worker->expects($this->once())
            ->method('process')
            ->willReturnCallback(function ($ctx, $workflow) use (&$workflowCaptured) {
                $workflowCaptured = $workflow;
            });

        $this->processor->process($task, $processHandler);

        $this->assertNotNull($workflowCaptured);

        $contextFailed = new Context([]);
        $contextConversationFailed = new ContextConversation($contextFailed, $conversation);
        $ctxFailed = new CodeContext($task, $contextConversationFailed);
        $ctxFailed->incrementDevRound();
        $ctxFailed->setTestedRound($ctxFailed->devRound());
        $ctxFailed->setTestResult(false);
        $nextNode = $workflowCaptured[Tester::class]($ctxFailed);
        $this->assertEquals(Developer::class, $nextNode);

        $contextNotFinished = new Context([]);
        $contextConversationNotFinished = new ContextConversation($contextNotFinished, $conversation);
        $ctxNotFinished = new CodeContext($task, $contextConversationNotFinished);
        $ctxNotFinished->incrementDevRound();
        $nextNode = $workflowCaptured[Tester::class]($ctxNotFinished);
        $this->assertEquals(Tester::class, $nextNode, 'Tester stays when testedRound !== devRound');
    }

    public function testWorkflowWaitMessageIsDeadEnd(): void
    {
        $task = new Task(
            id: 1,
            type: 'code',
            conversationId: 1,
            messages: [],
            context: [],
            projectId: 1,
            resultRequired: false,
        );

        $processHandler = $this->createMock(ProcessHandlerInterface::class);
        $conversation = new Chat();
        $handledConversation = new HandledConversation($conversation, $processHandler);

        $this->conversationFactory->method('handledConversation')->willReturn($handledConversation);

        $workflowCaptured = null;
        $this->worker->expects($this->once())
            ->method('process')
            ->willReturnCallback(function ($ctx, $workflow) use (&$workflowCaptured) {
                $workflowCaptured = $workflow;
            });

        $this->processor->process($task, $processHandler);

        $this->assertNotNull($workflowCaptured);
        $this->assertNull($workflowCaptured[WaitMessage::class], 'WaitMessage is dead-end node (null rule)');
    }

    public function testWorkflowDoAnswerWithoutRequestedTransitionGoesToWaitMessage(): void
    {
        $task = new Task(
            id: 1,
            type: 'code',
            conversationId: 1,
            messages: [],
            context: [],
            projectId: 1,
            resultRequired: false,
        );

        $processHandler = $this->createMock(ProcessHandlerInterface::class);
        $conversation = new Chat();
        $handledConversation = new HandledConversation($conversation, $processHandler);

        $this->conversationFactory->method('handledConversation')->willReturn($handledConversation);

        $workflowCaptured = null;
        $this->worker->expects($this->once())
            ->method('process')
            ->willReturnCallback(function ($ctx, $workflow) use (&$workflowCaptured) {
                $workflowCaptured = $workflow;
            });

        $this->processor->process($task, $processHandler);

        $this->assertNotNull($workflowCaptured);
        $conversationMock = $this->createMock(Conversation::class);
        $conversationMock->method('hasNoUserAnswer')->willReturn(false);
        $context = new Context([]);
        $contextConversation = new ContextConversation($context, $conversationMock);
        $ctx = new CodeContext($task, $contextConversation);
        $nextNode = $workflowCaptured[DoAnswer::class]($ctx);
        $this->assertEquals(WaitMessage::class, $nextNode, 'DoAnswer without requestedTransition goes to WaitMessage');
    }
}
