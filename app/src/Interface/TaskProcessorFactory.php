<?php

namespace Anymodule\Agentmodule\Interface;

interface TaskProcessorFactory
{
    public function createProcessorForTask(\Anymodule\Agentmodule\Entity\Task $task): TaskProcessor;
}