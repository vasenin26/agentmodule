<?php

namespace Anymodule\Agentmodule\Tests\Unit\Services\Workflows\Guard;

use Anymodule\Agentmodule\Services\Workflows\Guard\ExecutionPingPongGuard;
use PHPUnit\Framework\TestCase;

class ExecutionPingPongGuardTest extends TestCase
{
    public function testDoesNotThrowBelowMaxBounces(): void
    {
        $guard = new ExecutionPingPongGuard(10);

        $guard->checkTransition('A', 'B', 1);
        for ($i = 0; $i < 4; $i++) {
            $guard->checkTransition('B', 'A', 1);
            $guard->checkTransition('A', 'B', 1);
        }

        $this->addToAssertionCount(1);
    }

    public function testThrowsWhenAlternatingSingleResultTransitionsReachMaxBounces(): void
    {
        $guard = new ExecutionPingPongGuard(10);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/ping-pong loop/i');

        $guard->checkTransition('A', 'B', 1);
        for ($i = 0; $i < 20; $i++) {
            $guard->checkTransition('B', 'A', 1);
            $guard->checkTransition('A', 'B', 1);
        }
    }

    public function testTransitionsWithMoreThanOneStepResultDoNotCount(): void
    {
        $guard = new ExecutionPingPongGuard(2);

        // nodeStepResultsCount !== 1 means the node did real work before switching away,
        // so it must never count towards the ping-pong bounce total, however many times it repeats.
        for ($i = 0; $i < 20; $i++) {
            $guard->checkTransition('B', 'A', 3);
            $guard->checkTransition('A', 'B', 3);
        }

        $this->addToAssertionCount(1);
    }

    public function testStreakOfMoreThanOneResetsBeforeCountingSingleResultBounces(): void
    {
        $guard = new ExecutionPingPongGuard(2);

        $guard->checkTransition('A', 'B', 1); // bounce baseline
        $guard->checkTransition('B', 'A', 3); // does not count, and clears the streak
        $guard->checkTransition('A', 'B', 1); // starts a fresh streak (no prior transition to compare against), no throw

        $this->addToAssertionCount(1);
    }

    public function testRespectsCustomMaxBounces(): void
    {
        $guard = new ExecutionPingPongGuard(2);

        $this->expectException(\RuntimeException::class);

        $guard->checkTransition('A', 'B', 1);
        $guard->checkTransition('B', 'A', 1); // bounce 1
        $guard->checkTransition('A', 'B', 1); // bounce 2 -> throws
    }
}
