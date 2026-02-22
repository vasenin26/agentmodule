<?php

namespace Anymodule\Agentmodule\Services\Workflows\Interface;

interface StepNotifierInterface
{
    public function notifyStepStart(Context $ctx, string $step): void;
}
