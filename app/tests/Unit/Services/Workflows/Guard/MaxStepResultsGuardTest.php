<?php

namespace Anymodule\Agentmodule\Tests\Unit\Services\Workflows\Guard;

use Anymodule\Agentmodule\Services\Workflows\Guard\MaxStepResultsGuard;
use PHPUnit\Framework\TestCase;

class MaxStepResultsGuardTest extends TestCase
{
    public function testDoesNotThrowAtOrBelowMax(): void
    {
        $guard = new MaxStepResultsGuard(1000);

        $guard->check(999);
        $guard->check(1000);

        $this->addToAssertionCount(1);
    }

    public function testThrowsJustAboveMax(): void
    {
        $guard = new MaxStepResultsGuard(1000);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/1000 step results limit/');

        $guard->check(1001);
    }

    public function testRespectsCustomMax(): void
    {
        $guard = new MaxStepResultsGuard(5);

        $guard->check(5);

        $this->expectException(\RuntimeException::class);
        $guard->check(6);
    }
}
