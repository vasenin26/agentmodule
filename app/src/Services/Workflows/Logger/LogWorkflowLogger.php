<?php

namespace Anymodule\Agentmodule\Services\Workflows\Logger;

use Anymodule\Agentmodule\Application\Logger\Log;
use Anymodule\Agentmodule\Services\Workflows\Interface\WorkflowLoggerInterface;

class LogWorkflowLogger implements WorkflowLoggerInterface
{
    public function logNodeChange(string $from, string $to, bool $stopProcessing = false): void
    {
        Log::info($stopProcessing ? "Node changed, stop workflow processing" : "Node changed, skip node " . $from);
        Log::info("New node: " . $to);
    }
}
