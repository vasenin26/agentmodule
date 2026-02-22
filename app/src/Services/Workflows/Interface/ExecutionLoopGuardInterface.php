<?php

namespace Anymodule\Agentmodule\Services\Workflows\Interface;

interface ExecutionLoopGuardInterface
{
    public function checkTransition(string $from, string $to, int $nodeStepResultsCount): void;
}
