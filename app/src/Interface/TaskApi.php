<?php

namespace Anymodule\Agentmodule\Interface;

use Anymodule\Agentmodule\Entity\Task;

interface TaskApi
{
    public function getTask(): ?Task;
}