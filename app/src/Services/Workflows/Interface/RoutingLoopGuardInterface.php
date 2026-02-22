<?php

namespace Anymodule\Agentmodule\Services\Workflows\Interface;

interface RoutingLoopGuardInterface
{
    public function checkTransition(string $from, string $to): void;

    public function reset(): void;
}
