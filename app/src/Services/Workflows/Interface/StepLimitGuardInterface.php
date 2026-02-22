<?php

namespace Anymodule\Agentmodule\Services\Workflows\Interface;

interface StepLimitGuardInterface
{
    public function check(int $totalStepResults): void;
}
