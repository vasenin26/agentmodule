<?php

namespace Anymodule\Agentmodule\Services\Workflows\Interface;

interface WorkflowLoggerInterface
{
    public function logNodeChange(string $from, string $to, bool $stopProcessing = false): void;
}
