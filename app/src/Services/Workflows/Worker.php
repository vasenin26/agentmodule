<?php

namespace Anymodule\Agentmodule\Services\Workflows;

use Anymodule\Agentmodule\Interface\ProcessHandlerInterface;
use Anymodule\Agentmodule\Services\Workflows\Interface\Context;
use Anymodule\Agentmodule\Services\Workflows\Interface\ExecutionLoopGuardInterface;
use Anymodule\Agentmodule\Services\Workflows\Interface\NodeFactoryInterface;
use Anymodule\Agentmodule\Services\Workflows\Interface\NodeInterface;
use Anymodule\Agentmodule\Services\Workflows\Interface\ResultEmitterInterface;
use Anymodule\Agentmodule\Services\Workflows\Interface\RoutingLoopGuardInterface;
use Anymodule\Agentmodule\Services\Workflows\Interface\StepLimitGuardInterface;
use Anymodule\Agentmodule\Services\Workflows\Interface\StepNotifierInterface;
use Anymodule\Agentmodule\Services\Workflows\Interface\WorkflowLoggerInterface;
use Anymodule\Agentmodule\Services\Workflows\Interface\WorkflowWorker;

class Worker implements WorkflowWorker
{
    public function __construct(
        private NodeFactoryInterface $nodeFactory,
        private RoutingLoopGuardInterface $routingLoopGuard,
        private ExecutionLoopGuardInterface $executionLoopGuard,
        private StepLimitGuardInterface $stepLimitGuard,
        private StepNotifierInterface $stepNotifier,
        private ResultEmitterInterface $resultEmitter,
        private WorkflowLoggerInterface $workflowLogger,
    ) {
    }

    public function process(Context $ctx, array $workflow, ProcessHandlerInterface $handler): void
    {
        $totalStepResults = 0;
        $currentStep = $this->defineCurrentNode($ctx, $workflow, null);
        $stepWorker = null;

        while (!is_null($currentStep)) {

            $stepWorker = $this->getStepWorker($currentStep, $workflow);
            $currentStep = $this->defineCurrentNode($ctx, $workflow, $stepWorker);

            if ($stepWorker->getKey() !== $currentStep) {
                $fromNode = $stepWorker->getKey();
                $toNode = $currentStep;
                $this->workflowLogger->logNodeChange($fromNode, $toNode);
                $this->routingLoopGuard->checkTransition($fromNode, $toNode);
                continue;
            }

            $this->stepNotifier->notifyStepStart($ctx, $currentStep);
            $this->routingLoopGuard->reset();

            $nodeStepResultsCount = 0;
            foreach ($stepWorker->process($ctx) as $stepResult) {
                $nodeStepResultsCount++;
                $totalStepResults++;
                $this->stepLimitGuard->check($totalStepResults);

                $this->resultEmitter->emitStepResult($ctx, $stepResult, $handler);

                $currentStep = $this->defineCurrentNode($ctx, $workflow, $stepWorker);
                if ($stepWorker->getKey() !== $currentStep) {
                    $fromNode = $stepWorker->getKey();
                    $toNode = $currentStep;
                    $this->executionLoopGuard->checkTransition($fromNode, $toNode, $nodeStepResultsCount);
                    $this->workflowLogger->logNodeChange($fromNode, $toNode, true);
                    continue 2;
                }
            }

            $currentStep = null;
        }

        $this->resultEmitter->emitCompleted($ctx, $handler);
    }

    private function defineCurrentNode(Context $ctx, array $workflow, ?NodeInterface $step): ?string
    {
        $node = $step?->defineCurrentNode($ctx);
        return $node ?? array_key_first($workflow);
    }

    private function getStepWorker(string $nodeKey, array $workflow): NodeInterface
    {
        $rules = $workflow[$nodeKey] ?? null;

        if ($rules) {
            return $this->nodeFactory->createRuledNode($nodeKey, $rules);
        }

        return $this->nodeFactory->createDeadEndNode($nodeKey);
    }
}
