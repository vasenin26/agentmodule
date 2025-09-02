<?php

namespace Anymodule\Agentmodule\Interface;

use Anymodule\Agentmodule\Entity\Task;

interface TaskProcessor
{

    public function process(Task $task): void;
}