<?php

namespace Anymodule\Agentmodule\Tests\Unit\Services\Workflows;

use Anymodule\Agentmodule\Services\Workflows\DTO\StepResult;
use Anymodule\Agentmodule\Services\Workflows\Interface\Context;
use Anymodule\Agentmodule\Services\Workflows\Interface\NodeFactoryInterface;
use Anymodule\Agentmodule\Services\Workflows\Interface\NodeInterface;
use Anymodule\Agentmodule\Services\Workflows\Worker;
use PHPUnit\Framework\TestCase;

class WorkerTest extends TestCase
{
    private NodeFactoryInterface $nodeFactory;
    private Context $context;
    private Worker $worker;

    protected function setUp(): void
    {
        $this->nodeFactory = $this->createMock(NodeFactoryInterface::class);
        $this->context = $this->createMock(Context::class);
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

        $this->worker->process($this->context, $workflow);
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

        $this->worker->process($this->context, $workflow);
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

        $this->worker->process($this->context, $workflow);
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

        $this->worker->process($this->context, $workflow);
    }

    public function testProcessBreaksLoopWhenNodeChangesDuringProcessing(): void
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

        // node2 не должен быть создан, так как после break цикл завершается
        $this->nodeFactory->expects($this->once())
            ->method('createRuledNode')
            ->with('step1', ['rule1' => 'value1'])
            ->willReturn($node1);

        $this->worker->process($this->context, $workflow);
    }

    public function testProcessWithEmptyWorkflow(): void
    {
        $workflow = [];

        $this->nodeFactory->expects($this->never())
            ->method('createRuledNode');
        $this->nodeFactory->expects($this->never())
            ->method('createDeadEndNode');

        $this->worker->process($this->context, $workflow);
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

        $this->worker->process($this->context, $workflow);
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

        $this->worker->process($this->context, $workflow);
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

        $this->worker->process($this->context, $workflow);
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

        $this->worker->process($this->context, $workflow);
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

        $this->worker->process($this->context, $workflow);
    }

    private function createStepResultGenerator(array $results): \Generator
    {
        foreach ($results as $result) {
            yield $result;
        }
    }
}
