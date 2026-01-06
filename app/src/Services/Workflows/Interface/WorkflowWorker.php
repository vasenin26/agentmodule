<?php

namespace Anymodule\Agentmodule\Services\Workflows\Interface;

interface WorkflowWorker
{
    public function process(Context $ctx, array $workflow): void;
}