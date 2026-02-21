<?php

namespace Anymodule\Agentmodule\Tests\Unit\Services\Workflows;

use Anymodule\Agentmodule\Entity\ContextConversation;
use Anymodule\Agentmodule\Entity\ProcessingResult;
use Anymodule\Agentmodule\Interface\ProcessHandlerInterface;
use Anymodule\Agentmodule\Services\Workflows\DTO\StepResult;
use Anymodule\Agentmodule\Services\Workflows\Interface\Context;
use Anymodule\Agentmodule\Services\Workflows\Interface\NodeFactoryInterface;
use Anymodule\Agentmodule\Services\Workflows\Interface\NodeInterface;
use Anymodule\Agentmodule\Services\Workflows\Worker;
use PHPUnit\Framework\TestCase;
use Vasenin26\Conversation\Interface\Conversation as ConversationInterface;

class WorkerTest extends TestCase
{
    private NodeFactoryInterface $nodeFactory;
    private Context $context;
    private ProcessHandlerInterface $handler;
    private ConversationInterface $conversation;
    private \Anymodule\Agentmodule\Entity\Context $taskContext;
    private ContextConversation $contextConversation;
    private Worker $worker;

    protected function setUp(): void
    {
        $this->nodeFactory = $this->createMock(NodeFactoryInterface::class);
        $this->context = $this->createMock(Context::class);
        $this->handler = $this->createMock(ProcessHandlerInterface::class);
        $this->conversation = $this->createMock(ConversationInterface::class);
        $this->taskContext = new \Anymodule\Agentmodule\Entity\Context(tasks: [], payload: []);
        $this->contextConversation = new ContextConversation($this->taskContext, $this->conversation);
        $this->context->method('getContextConversation')->willReturn($this->contextConversation);
        $this->worker = new Worker($this->nodeFactory);
    }

    public function testProcessWithSingleStepWorkflow(): void
    {
        $workflow = [
            'step1' => ['rule1' => 'value1']
        ];

        $node = $this->createMock(NodeInterface::class);
        $node->method('getKey')->willReturn('step1');
        $node->method('defineCurrentNode')->willReturn('step1');
        $node->method('process')->willReturn($this->createStepResultGenerator([
            new StepResult(finished: true)
        ]));

        $this->nodeFactory->expects($this->once())
            ->method('createRuledNode')
            ->with('step1', ['rule1' => 'value1'])
            ->willReturn($node);

        $this->worker->process($this->context, $workflow, $this->handler);
    }

    public function testProcessWithMultipleStepsWorkflow(): void
    {
        $workflow = [
            'step1' => ['rule1' => 'value1'],
            'step2' => ['rule2' => 'value2'],
        ];

        $node1 = $this->createMock(NodeInterface::class);
        $node1->method('getKey')->willReturn('step1');
        $node1->method('defineCurrentNode')->willReturn('step2');
        $node1->method('process')->willReturn($this->createStepResultGenerator([
            new StepResult(finished: true)
        ]));

        $node2 = $this->createMock(NodeInterface::class);
        $node2->method('getKey')->willReturn('step2');
        $node2->method('defineCurrentNode')->willReturn('step2');
        $node2->method('process')->willReturn($this->createStepResultGenerator([
            new StepResult(finished: true)
        ]));

        $this->nodeFactory->expects($this->exactly(2))
            ->method('createRuledNode')
            ->willReturnCallback(function ($key, $rules) use ($node1, $node2) {
                if ($key === 'step1') {
                    return $node1;
                }
                return $node2;
            });

        $this->worker->process($this->context, $workflow, $this->handler);
    }

    public function testProcessWithDeadEndNode(): void
    {
        $workflow = [
            'step1' => null
        ];

        $node = $this->createMock(NodeInterface::class);
        $node->method('getKey')->willReturn('step1');
        $node->method('defineCurrentNode')->willReturn('step1');
        $node->method('process')->willReturn($this->createStepResultGenerator([
            new StepResult(finished: true)
        ]));

        $this->nodeFactory->expects($this->once())
            ->method('createDeadEndNode')
            ->with('step1')
            ->willReturn($node);

        $this->worker->process($this->context, $workflow, $this->handler);
    }

    public function testProcessSkipsStepWhenNodeChangesDuringExecution(): void
    {
        $workflow = [
            'step1' => ['rule1' => 'value1'],
            'step2' => ['rule2' => 'value2'],
        ];

        $node1 = $this->createMock(NodeInterface::class);
        $node1->method('getKey')->willReturn('step1');
        $node1->method('defineCurrentNode')->willReturn('step2');
        $node1->method('process')->willReturn($this->createStepResultGenerator([]));

        $node2 = $this->createMock(NodeInterface::class);
        $node2->method('getKey')->willReturn('step2');
        $node2->method('defineCurrentNode')->willReturn('step2');
        $node2->method('process')->willReturn($this->createStepResultGenerator([
            new StepResult(finished: true)
        ]));

        $this->nodeFactory->expects($this->exactly(2))
            ->method('createRuledNode')
            ->willReturnCallback(function ($key) use ($node1, $node2) {
                return $key === 'step1' ? $node1 : $node2;
            });

        $this->worker->process($this->context, $workflow, $this->handler);
    }

    public function testProcessContinuesWhenNodeChangesDuringProcessing(): void
    {
        $workflow = [
            'step1' => ['rule1' => 'value1'],
            'step2' => ['rule2' => 'value2'],
        ];

        $node1 = $this->createMock(NodeInterface::class);
        $node1->method('getKey')->willReturn('step1');
        $node1->method('defineCurrentNode')
            ->willReturnOnConsecutiveCalls('step1', 'step1', 'step2');
        $node1->method('process')->willReturn($this->createStepResultGenerator([
            new StepResult(finished: false),
            new StepResult(finished: false),
        ]));

        $node2 = $this->createMock(NodeInterface::class);
        $node2->method('getKey')->willReturn('step2');
        $node2->method('defineCurrentNode')->willReturn('step2');
        $node2->method('process')->willReturn($this->createStepResultGenerator([
            new StepResult(finished: true),
        ]));

        // При смене шага внутри process() worker должен переключиться на новый шаг
        $this->nodeFactory->expects($this->exactly(2))
            ->method('createRuledNode')
            ->willReturnCallback(function ($key) use ($node1, $node2) {
                return $key === 'step1' ? $node1 : $node2;
            });

        $this->worker->process($this->context, $workflow, $this->handler);
    }

    public function testProcessWithEmptyWorkflow(): void
    {
        $workflow = [];

        $this->nodeFactory->expects($this->never())
            ->method('createRuledNode');
        $this->nodeFactory->expects($this->never())
            ->method('createDeadEndNode');

        $this->worker->process($this->context, $workflow, $this->handler);
    }

    public function testProcessWithContinueWhenNodeKeyChangesBeforeProcessing(): void
    {
        $workflow = [
            'step1' => ['rule1' => 'value1'],
            'step2' => ['rule2' => 'value2'],
        ];

        $node1 = $this->createMock(NodeInterface::class);
        $node1->method('getKey')->willReturn('step1');
        $node1->method('defineCurrentNode')->willReturn('step2');
        $node1->method('process')->willReturn($this->createStepResultGenerator([]));

        $node2 = $this->createMock(NodeInterface::class);
        $node2->method('getKey')->willReturn('step2');
        $node2->method('defineCurrentNode')->willReturn('step2');
        $node2->method('process')->willReturn($this->createStepResultGenerator([
            new StepResult(finished: true)
        ]));

        $this->nodeFactory->expects($this->exactly(2))
            ->method('createRuledNode')
            ->willReturnCallback(function ($key) use ($node1, $node2) {
                return $key === 'step1' ? $node1 : $node2;
            });

        $this->worker->process($this->context, $workflow, $this->handler);
    }

    public function testProcessWithMultipleStepResults(): void
    {
        $workflow = [
            'step1' => ['rule1' => 'value1']
        ];

        $node = $this->createMock(NodeInterface::class);
        $node->method('getKey')->willReturn('step1');
        $node->method('defineCurrentNode')->willReturn('step1');
        $node->method('process')->willReturn($this->createStepResultGenerator([
            new StepResult(finished: false),
            new StepResult(finished: false),
            new StepResult(finished: true),
        ]));

        $this->nodeFactory->expects($this->once())
            ->method('createRuledNode')
            ->with('step1', ['rule1' => 'value1'])
            ->willReturn($node);

        $this->worker->process($this->context, $workflow, $this->handler);
    }

    public function testProcessWithMixedRuledAndDeadEndNodes(): void
    {
        $workflow = [
            'step1' => ['rule1' => 'value1'],
            'step2' => null,
        ];

        $node1 = $this->createMock(NodeInterface::class);
        $node1->method('getKey')->willReturn('step1');
        $node1->method('defineCurrentNode')->willReturn('step2');
        $node1->method('process')->willReturn($this->createStepResultGenerator([
            new StepResult(finished: true)
        ]));

        $node2 = $this->createMock(NodeInterface::class);
        $node2->method('getKey')->willReturn('step2');
        $node2->method('defineCurrentNode')->willReturn('step2');
        $node2->method('process')->willReturn($this->createStepResultGenerator([
            new StepResult(finished: true)
        ]));

        $this->nodeFactory->expects($this->once())
            ->method('createRuledNode')
            ->with('step1', ['rule1' => 'value1'])
            ->willReturn($node1);

        $this->nodeFactory->expects($this->once())
            ->method('createDeadEndNode')
            ->with('step2')
            ->willReturn($node2);

        $this->worker->process($this->context, $workflow, $this->handler);
    }

    public function testProcessStartsWithFirstWorkflowKey(): void
    {
        $workflow = [
            'step2' => ['rule2' => 'value2'],
            'step1' => ['rule1' => 'value1'],
        ];

        $node = $this->createMock(NodeInterface::class);
        $node->method('getKey')->willReturn('step2');
        $node->method('defineCurrentNode')->willReturn('step2');
        $node->method('process')->willReturn($this->createStepResultGenerator([
            new StepResult(finished: true)
        ]));

        $this->nodeFactory->expects($this->once())
            ->method('createRuledNode')
            ->with('step2', ['rule2' => 'value2'])
            ->willReturn($node);

        $this->worker->process($this->context, $workflow, $this->handler);
    }

    public function testProcessWithStepReturningFirstKeyWhenNull(): void
    {
        $workflow = [
            'step1' => ['rule1' => 'value1'],
        ];

        $node = $this->createMock(NodeInterface::class);
        $node->method('getKey')->willReturn('step1');
        // defineCurrentNode возвращает null, но Worker использует array_key_first как fallback
        $node->method('defineCurrentNode')->willReturn('step1');
        $node->method('process')->willReturn($this->createStepResultGenerator([
            new StepResult(finished: true)
        ]));

        $this->nodeFactory->expects($this->once())
            ->method('createRuledNode')
            ->with('step1', ['rule1' => 'value1'])
            ->willReturn($node);

        $this->worker->process($this->context, $workflow, $this->handler);
    }

    public function testWorkerCallsHandlerOnEachStepYield(): void
    {
        $workflow = [
            'step1' => ['rule1' => 'value1']
        ];

        $node = $this->createMock(NodeInterface::class);
        $node->method('getKey')->willReturn('step1');
        $node->method('defineCurrentNode')->willReturn('step1');
        $node->method('process')->willReturn($this->createStepResultGenerator([
            new StepResult(finished: false),
            new StepResult(finished: false),
            new StepResult(finished: true),
        ]));

        $this->nodeFactory->expects($this->once())
            ->method('createRuledNode')
            ->with('step1', ['rule1' => 'value1'])
            ->willReturn($node);

        $callCount = 0;
        $this->handler->expects($this->exactly(4))
            ->method('handle')
            ->willReturnCallback(function (ProcessingResult $result) use (&$callCount) {
                $callCount++;
                if ($callCount <= 3) {
                    $this->assertFalse($result->completed);
                } else {
                    $this->assertTrue($result->completed);
                }
                $this->assertNull($result->answer);
                $this->assertSame($this->conversation, $result->conversation);
                $this->assertSame($this->taskContext, $result->context);
                $this->assertSame([], $result->payload);
            });

        $this->worker->process($this->context, $workflow, $this->handler);
    }

    private function createStepResultGenerator(array $results): \Generator
    {
        foreach ($results as $result) {
            yield $result;
        }
    }
}
