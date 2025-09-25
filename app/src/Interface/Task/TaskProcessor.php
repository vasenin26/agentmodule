<?php

namespace Anymodule\Agentmodule\Interface\Task;

use Anymodule\Agentmodule\Entity\ProcessingResult;
use Anymodule\Agentmodule\Entity\Task;

interface TaskProcessor
{

    public function process(Task $task, $processHandler): ProcessingResult;
}