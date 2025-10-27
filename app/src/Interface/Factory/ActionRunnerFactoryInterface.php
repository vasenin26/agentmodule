<?php

namespace Anymodule\Agentmodule\Interface\Factory;

use Anymodule\Agentmodule\Interface\ActionRunnerInterface;

interface ActionRunnerFactoryInterface
{

    public function createForTask(\Anymodule\Agentmodule\Entity\Task $task, array $actions): ActionRunnerInterface;
}