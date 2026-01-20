<?php

namespace Anymodule\Agentmodule\Services\Workflows\Interface;

use Anymodule\Agentmodule\Interface\ProcessHandlerInterface;

interface WorkflowWorker
{
    public function process(Context $ctx, array $workflow, ProcessHandlerInterface $handler): void;
}