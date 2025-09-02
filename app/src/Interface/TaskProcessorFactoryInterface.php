<?php

namespace Anymodule\Agentmodule\Interface;

interface TaskProcessorFactoryInterface
{
    public function createProcessorForTask(\Anymodule\Agentmodule\Entity\Task $task): TaskProcessor;
}