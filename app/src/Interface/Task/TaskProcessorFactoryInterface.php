<?php

namespace Anymodule\Agentmodule\Interface\Task;

interface TaskProcessorFactoryInterface
{
    public function createProcessorForTask(\Anymodule\Agentmodule\Entity\Task $task): TaskProcessor;
}