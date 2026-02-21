<?php

namespace Anymodule\Agentmodule\Services\Workflows;

use Anymodule\Agentmodule\Entity\ProcessingResult;
use Anymodule\Agentmodule\Interface\ProcessHandlerInterface;
use Anymodule\Agentmodule\Services\Workflows\Interface\Context;
use Anymodule\Agentmodule\Services\Workflows\Interface\NodeFactoryInterface;
use Anymodule\Agentmodule\Services\Workflows\Interface\NodeInterface;
use Anymodule\Agentmodule\Services\Workflows\Interface\WorkflowWorker;
use Anymodule\Agentmodule\Application\Logger\Log;
use Vasenin26\Conversation\Messages\InfoMessage;

class Worker implements WorkflowWorker
{
    public function __construct(
        private NodeFactoryInterface $nodeFactory,
    )
    {
    }

    public function process(Context $ctx, array $workflow, ProcessHandlerInterface $handler): void
    {
        $maxTotalStepResults = 1000;
        $totalStepResults = 0;

        // Detect ping-pong loops like: node1 -> node2 -> node1 -> node2 ...
        // Count only transitions where the node produced exactly ONE StepResult.
        $maxPingPongBounces = 10;
        $pingPongBounces = 0;
        $lastTransitionFrom = null;
        $lastTransitionTo = null;

        // Detect router-only ping-pong loops (node changes without executing nodes).
        $routingPingPongBounces = 0;
        $lastRoutingFrom = null;
        $lastRoutingTo = null;

        $currentStep = $this->defineCurrentNode($ctx, $workflow, null);
        $stepWorker = null;
        $contextConversation = $ctx->getContextConversation();

        while (!is_null($currentStep)) {

            $stepWorker = $this->getStepWorker($currentStep, $workflow);
            $currentStep = $this->defineCurrentNode($ctx, $workflow, $stepWorker);

            if ($stepWorker->getKey() !== $currentStep) {
                Log::info("Node changed, skip node " . $stepWorker->getKey());
                Log::info("New node: " . $currentStep);

                $fromNode = $stepWorker->getKey();
                $toNode = $currentStep;

                if (
                    $lastRoutingFrom !== null
                    && $lastRoutingTo !== null
                    && $fromNode === $lastRoutingTo
                    && $toNode === $lastRoutingFrom
                ) {
                    $routingPingPongBounces++;
                } else {
                    $routingPingPongBounces = 0;
                }

                $lastRoutingFrom = $fromNode;
                $lastRoutingTo = $toNode;

                if ($routingPingPongBounces >= $maxPingPongBounces) {
                    throw new \RuntimeException(
                        "Detected router ping-pong loop between nodes: {$lastRoutingFrom} <-> {$lastRoutingTo}"
                    );
                }
                continue;
            }

            $ctx->getContextConversation()->conversation->addMessage(new InfoMessage('Current step: ' . $currentStep));

            // Node is actually executing; reset router-only loop detection.
            $routingPingPongBounces = 0;
            $lastRoutingFrom = null;
            $lastRoutingTo = null;

            $nodeStepResultsCount = 0;
            foreach ($stepWorker->process($ctx) as $stepResult) {
                $nodeStepResultsCount++;
                $totalStepResults++;
                if ($totalStepResults > $maxTotalStepResults) {
                    throw new \RuntimeException("Workflow exceeded {$maxTotalStepResults} step results limit");
                }

                $contextConversation = $ctx->getContextConversation();

                $handler->handle(new ProcessingResult(
                    completed: false,
                    answer: null,
                    conversation: $contextConversation->conversation,
                    context: $contextConversation->context,
                    modelName: $stepResult->modelName,
                    contextFill: $stepResult->contextFill ?? 0,
                    promptTokens: $stepResult->promptTokens ?? 0,
                    completionTokens: $stepResult->completionTokens ?? 0,
                    totalTokens: $stepResult->totalTokens ?? 0,
                    payload: [],
                ));

                $currentStep = $this->defineCurrentNode($ctx, $workflow, $stepWorker);
                if ($stepWorker->getKey() !== $currentStep) {
                    $fromNode = $stepWorker->getKey();
                    $toNode = $currentStep;

                    // Ping-pong detection only for nodes that yield exactly ONE step result.
                    if ($nodeStepResultsCount === 1) {
                        if (
                            $lastTransitionFrom !== null
                            && $lastTransitionTo !== null
                            && $fromNode === $lastTransitionTo
                            && $toNode === $lastTransitionFrom
                        ) {
                            $pingPongBounces++;
                        } else {
                            $pingPongBounces = 0;
                        }

                        $lastTransitionFrom = $fromNode;
                        $lastTransitionTo = $toNode;

                        if ($pingPongBounces >= $maxPingPongBounces) {
                            throw new \RuntimeException(
                                "Detected ping-pong loop between nodes: {$lastTransitionFrom} <-> {$lastTransitionTo}"
                            );
                        }
                    } else {
                        // Consider transition justified; reset loop detection.
                        $pingPongBounces = 0;
                        $lastTransitionFrom = null;
                        $lastTransitionTo = null;
                    }

                    Log::info("Node changed, stop workflow processing");
                    Log::info("New node: " . $currentStep);
                    continue 2;
                }
            }

            $currentStep = null;
        }
        
        $handler->handle(new ProcessingResult(
            completed: true,
            answer: null,
            conversation: $contextConversation->conversation,
            context: $contextConversation->context,
            modelName: null,
            contextFill: 0,
            promptTokens: 0,
            completionTokens: 0,
            totalTokens: 0,
            payload: [],
        ));
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
