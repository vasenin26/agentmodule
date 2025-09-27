<?php

namespace Anymodule\Agentmodule\Interface\Task;

use Anymodule\Agentmodule\Entity\Task;
use Anymodule\Agentmodule\Interface\ProcessHandlerInterface;

interface TaskProcessor
{
    public function process(Task $task, ProcessHandlerInterface $processHandler): void;
}