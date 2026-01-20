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
use Anymodule\Agentmodule\Interface\Factory\ConversationFactoryInterface;
use Anymodule\Agentmodule\Interface\ProcessHandlerInterface;
use Anymodule\Agentmodule\Services\Workflows\Interface\WorkflowWorker;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Vasenin26\Conversation\Chat;
use Vasenin26\Conversation\Interface\Conversation;

class CodeWorkflowTest extends TestCase
{
    private WorkflowWorker|MockObject $worker;
    private ConversationFactoryInterface|MockObject $conversationFactory;
    private CodeWorkflow $processor;

    protected function setUp(): void
    {
        $this->worker = $this->createMock(WorkflowWorker::class);
        $this->conversationFactory = $this->createMock(ConversationFactoryInterface::class);
        $this->processor = new CodeWorkflow($this->worker, $this->conversationFactory);
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
                    return isset($workflow[CodePlanner::class])
                        && isset($workflow[Developer::class])
                        && isset($workflow[Tester::class])
                        && isset($workflow[WaitMessage::class]);
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

                    // Проверяем, что правила переходов - это callable функции
                    $this->assertIsCallable($workflow[CodePlanner::class]);
                    $this->assertIsCallable($workflow[Developer::class]);
                    $this->assertIsCallable($workflow[Tester::class]);
                    $this->assertIsCallable($workflow[WaitMessage::class]);

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
        
        // Важно: state хранится в Context->payload, поэтому используем разные Context для разных сценариев
        $contextFinished = new Context([]);
        $contextConversationFinished = new ContextConversation($contextFinished, $conversation);
        
        // Проверяем переход Developer -> Tester когда код завершен
        $ctxFinished = new CodeContext($task, $contextConversationFinished);
        $ctxFinished->finishCode();
        $nextNode = $workflowCaptured[Developer::class]($ctxFinished);
        $this->assertEquals(Tester::class, $nextNode);

        // Проверяем, что Developer остается на месте когда код не завершен
        $contextNotFinished = new Context([]);
        $contextConversationNotFinished = new ContextConversation($contextNotFinished, $conversation);
        $ctxNotFinished = new CodeContext($task, $contextConversationNotFinished);
        $nextNode = $workflowCaptured[Developer::class]($ctxNotFinished);
        $this->assertEquals(Developer::class, $nextNode);
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
                    
                    // Проверяем переход Tester -> WaitMessage когда тесты успешны
                    $ctx->method('testFinished')->willReturn(true);
                    $ctx->method('testSucceed')->willReturn(true);
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
        
        // Важно: state хранится в Context->payload, поэтому используем разные Context для разных сценариев
        $contextFailed = new Context([]);
        $contextConversationFailed = new ContextConversation($contextFailed, $conversation);
        
        // Проверяем переход Tester -> Developer когда тесты неудачны
        $ctxFailed = new CodeContext($task, $contextConversationFailed);
        $ctxFailed->setTestResult(false);
        $nextNode = $workflowCaptured[Tester::class]($ctxFailed);
        $this->assertEquals(Developer::class, $nextNode);

        // Проверяем, что Tester остается на месте когда тесты не завершены
        $contextNotFinished = new Context([]);
        $contextConversationNotFinished = new ContextConversation($contextNotFinished, $conversation);
        $ctxNotFinished = new CodeContext($task, $contextConversationNotFinished);
        $nextNode = $workflowCaptured[Tester::class]($ctxNotFinished);
        $this->assertEquals(Tester::class, $nextNode);
    }

    public function testWorkflowWaitMessageStopsWhenNoMessage(): void
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
        // Пустой чат означает, что нет сообщений от пользователя (hasNoUserAnswer() = true)
        // hasMessage() возвращает hasNoUserAnswer(), то есть true когда нет ответа пользователя
        // Но в нашем случае мы хотим проверить остановку, когда hasMessage() = false
        // Это означает, что hasNoUserAnswer() = false, то есть есть ответ пользователя
        // Но мы проверяем остановку, когда НЕТ сообщения для обработки
        // hasMessage() = false означает, что нет сообщения для обработки (не нужно переходить к DoAnswer)
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
        
        $context = new Context([]);
        // Создаем чат, где hasNoUserAnswer() = false (есть ответ пользователя)
        // Это означает hasMessage() = false, то есть нет сообщения для обработки
        $conversationWithAnswer = new Chat();
        $contextConversation = new ContextConversation($context, $conversationWithAnswer);
        $ctx = new CodeContext($task, $contextConversation);
        
        // hasMessage() проверяет hasNoUserAnswer()
        // Если hasNoUserAnswer() = false (есть ответ), то hasMessage() = false
        // В этом случае WaitMessage должен остаться на месте
        // Но нужно проверить реальное поведение - когда чат пустой, hasNoUserAnswer() = true
        // Поэтому hasMessage() = true, и мы переходим к DoAnswer
        // Для остановки нужно, чтобы hasNoUserAnswer() = false (есть ответ пользователя)
        // Но это означает, что hasMessage() = false, и WaitMessage остается
        
        // Создаем мок Conversation, где hasNoUserAnswer() = false
        $conversationMock = $this->createMock(Conversation::class);
        $conversationMock->method('hasNoUserAnswer')->willReturn(false);
        $contextConversationMock = new ContextConversation($context, $conversationMock);
        $ctxNoMessage = new CodeContext($task, $contextConversationMock);
        
        $nextNode = $workflowCaptured[WaitMessage::class]($ctxNoMessage);
        $this->assertEquals(WaitMessage::class, $nextNode, 'WaitMessage should stay when no message to process');
    }

    public function testWorkflowWaitMessageToDoAnswerWhenMessageExists(): void
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
        
        $context = new Context([]);
        // hasMessage() возвращает hasNoUserAnswer()
        // Если hasNoUserAnswer() = true (нет ответа пользователя), то hasMessage() = true
        // В этом случае нужно перейти к DoAnswer для обработки сообщения
        $conversationMock = $this->createMock(Conversation::class);
        $conversationMock->method('hasNoUserAnswer')->willReturn(true);
        $contextConversation = new ContextConversation($context, $conversationMock);
        $ctx = new CodeContext($task, $contextConversation);
        
        $nextNode = $workflowCaptured[WaitMessage::class]($ctx);
        $this->assertEquals(DoAnswer::class, $nextNode);
    }
}
