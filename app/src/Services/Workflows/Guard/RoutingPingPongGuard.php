<?php

namespace Anymodule\Agentmodule\Services\Workflows\Guard;

use Anymodule\Agentmodule\Services\Workflows\Interface\RoutingLoopGuardInterface;

class RoutingPingPongGuard implements RoutingLoopGuardInterface
{
    private ?string $lastFrom = null;
    private ?string $lastTo = null;
    private int $bounces = 0;

    public function __construct(
        private int $maxBounces = 10,
    ) {
    }

    public function checkTransition(string $from, string $to): void
    {
        if (
            $this->lastFrom !== null
            && $this->lastTo !== null
            && $from === $this->lastTo
            && $to === $this->lastFrom
        ) {
            $this->bounces++;
        } else {
            $this->bounces = 0;
        }

        $this->lastFrom = $from;
        $this->lastTo = $to;

        if ($this->bounces >= $this->maxBounces) {
            throw new \RuntimeException(
                "Detected router ping-pong loop between nodes: {$this->lastFrom} <-> {$this->lastTo}"
            );
        }
    }

    public function reset(): void
    {
        $this->bounces = 0;
        $this->lastFrom = null;
        $this->lastTo = null;
    }
}
