<?php

namespace Anymodule\Agentmodule\Interface\Task;

use Anymodule\Agentmodule\Entity\Task;
use Anymodule\Agentmodule\Interface\ProcessHandlerInterface;

interface TaskProcessor
{
    public function supports(Task $task): bool;
    public function process(Task $task, ProcessHandlerInterface $processHandler): void;
}