<?php

namespace Anymodule\Agentmodule\Services\Workflows;

use Anymodule\Agentmodule\Interface\ProcessHandlerInterface;
use Anymodule\Agentmodule\Services\Workflows\Interface\Context;
use Anymodule\Agentmodule\Services\Workflows\Interface\NodeFactoryInterface;
use Anymodule\Agentmodule\Services\Workflows\Interface\NodeInterface;
use Anymodule\Agentmodule\Services\Workflows\Interface\WorkflowWorker;

class Worker implements WorkflowWorker
{
    public function __construct(
        private NodeFactoryInterface $nodeFactory,
    )
    {
    }

    public function process(Context $ctx, array $workflow): void
    {
        $currentStep = $this->defineCurrentNode($ctx, $workflow, null);
        $stepWorker = null;

        while (!is_null($currentStep)) {
            $stepWorker = $this->getStepWorker($currentStep, $workflow);

            $currentStep = $this->defineCurrentNode($ctx, $workflow, $stepWorker);
            if ($stepWorker->getKey() !== $currentStep) {
                continue;
            }

            foreach ($stepWorker->process($ctx) as $stepResult) {
                $currentStep = $this->defineCurrentNode($ctx, $workflow, $stepWorker);
                if ($stepWorker->getKey() !== $currentStep) {
                    break;
                }
            }

            $currentStep = null;
        }
    }

    private function defineStartStep(Context $ctx, array $workflow): string
    {
        return array_key_first($workflow);
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