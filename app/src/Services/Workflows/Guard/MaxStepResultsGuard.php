<?php

namespace Anymodule\Agentmodule\Services\Workflows\Guard;

use Anymodule\Agentmodule\Services\Workflows\Interface\StepLimitGuardInterface;

class MaxStepResultsGuard implements StepLimitGuardInterface
{
    public function __construct(
        private int $max = 1000,
    ) {
    }

    public function check(int $totalStepResults): void
    {
        if ($totalStepResults > $this->max) {
            throw new \RuntimeException("Workflow exceeded {$this->max} step results limit");
        }
    }
}
