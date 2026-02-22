<?php

namespace Anymodule\Agentmodule\Services\Workflows\Guard;

use Anymodule\Agentmodule\Services\Workflows\Interface\ExecutionLoopGuardInterface;

class ExecutionPingPongGuard implements ExecutionLoopGuardInterface
{
    private ?string $lastTransitionFrom = null;
    private ?string $lastTransitionTo = null;
    private int $pingPongBounces = 0;

    public function __construct(
        private int $maxPingPongBounces = 10,
    ) {
    }

    public function checkTransition(string $from, string $to, int $nodeStepResultsCount): void
    {
        if ($nodeStepResultsCount === 1) {
            if (
                $this->lastTransitionFrom !== null
                && $this->lastTransitionTo !== null
                && $from === $this->lastTransitionTo
                && $to === $this->lastTransitionFrom
            ) {
                $this->pingPongBounces++;
            } else {
                $this->pingPongBounces = 0;
            }

            $this->lastTransitionFrom = $from;
            $this->lastTransitionTo = $to;

            if ($this->pingPongBounces >= $this->maxPingPongBounces) {
                throw new \RuntimeException(
                    "Detected ping-pong loop between nodes: {$this->lastTransitionFrom} <-> {$this->lastTransitionTo}"
                );
            }
        } else {
            $this->pingPongBounces = 0;
            $this->lastTransitionFrom = null;
            $this->lastTransitionTo = null;
        }
    }
}
