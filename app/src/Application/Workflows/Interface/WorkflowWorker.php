<?php

namespace Anymodule\Agentmodule\Application\Workflows\Interface;

interface WorkflowWorker
{
    public function process(\Anymodule\Agentmodule\Entity\Task $task, array $workflow, \Anymodule\Agentmodule\Interface\ProcessHandlerInterface $processHandler): void;
}