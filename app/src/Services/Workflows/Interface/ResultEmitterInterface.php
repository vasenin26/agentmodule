<?php

namespace Anymodule\Agentmodule\Services\Workflows\Interface;

use Anymodule\Agentmodule\Interface\ProcessHandlerInterface;
use Anymodule\Agentmodule\Services\Workflows\DTO\StepResult;

interface ResultEmitterInterface
{
    public function emitStepResult(Context $ctx, StepResult $stepResult, ProcessHandlerInterface $handler): void;

    public function emitCompleted(Context $ctx, ProcessHandlerInterface $handler): void;
}
