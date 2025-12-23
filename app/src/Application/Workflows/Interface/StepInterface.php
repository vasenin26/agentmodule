<?php

namespace Anymodule\Agentmodule\Application\Workflows\Interface;

use Anymodule\Agentmodule\Application\Workflows\DTO\StepResult;
use Anymodule\Agentmodule\Entity\Task;
use Anymodule\Agentmodule\Interface\ProcessHandlerInterface;

interface StepInterface
{
    public function process(Task $task, ProcessHandlerInterface $processHandler): StepResult;
}