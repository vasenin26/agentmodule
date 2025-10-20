<?php

namespace Anymodule\Agentmodule\Interface;

interface ActionRunnerFactoryInterface
{

    public function createForTask(\Anymodule\Agentmodule\Entity\Task $task, array $actions): ActionRunnerInterface;
}