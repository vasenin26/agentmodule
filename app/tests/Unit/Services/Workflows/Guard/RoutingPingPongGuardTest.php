<?php

namespace Anymodule\Agentmodule\Tests\Unit\Services\Workflows\Guard;

use Anymodule\Agentmodule\Services\Workflows\Guard\RoutingPingPongGuard;
use PHPUnit\Framework\TestCase;

class RoutingPingPongGuardTest extends TestCase
{
    public function testDoesNotThrowBelowMaxBounces(): void
    {
        $guard = new RoutingPingPongGuard(10);

        // Each alternating call adds 1 bounce; 8 calls after the first stays at 8, below the max of 10.
        $guard->checkTransition('A', 'B');
        for ($i = 0; $i < 4; $i++) {
            $guard->checkTransition('B', 'A');
            $guard->checkTransition('A', 'B');
        }

        $this->addToAssertionCount(1);
    }

    public function testThrowsWhenAlternatingTransitionsReachMaxBounces(): void
    {
        $guard = new RoutingPingPongGuard(10);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/ping-pong loop/i');

        $guard->checkTransition('A', 'B');
        for ($i = 0; $i < 20; $i++) {
            $guard->checkTransition('B', 'A');
            $guard->checkTransition('A', 'B');
        }
    }

    public function testNonAlternatingTransitionsResetTheCounter(): void
    {
        $guard = new RoutingPingPongGuard(3);

        $guard->checkTransition('A', 'B');
        $guard->checkTransition('B', 'A'); // bounce 1
        $guard->checkTransition('B', 'A'); // not the reverse of the last transition -> resets
        $guard->checkTransition('A', 'B'); // bounce 1 again, not 2

        $this->addToAssertionCount(1);
    }

    public function testResetClearsAccumulatedState(): void
    {
        $guard = new RoutingPingPongGuard(3);

        $guard->checkTransition('A', 'B');
        $guard->checkTransition('B', 'A'); // bounce 1
        $guard->reset();

        // Without the reset, one more alternating pair would trip the max of 3.
        $guard->checkTransition('A', 'B');
        $guard->checkTransition('B', 'A');

        $this->addToAssertionCount(1);
    }

    public function testRespectsCustomMaxBounces(): void
    {
        $guard = new RoutingPingPongGuard(2);

        $this->expectException(\RuntimeException::class);

        $guard->checkTransition('A', 'B');
        $guard->checkTransition('B', 'A'); // bounce 1
        $guard->checkTransition('A', 'B'); // bounce 2 -> throws
    }
}
