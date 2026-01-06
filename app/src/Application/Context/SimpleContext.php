<?php

namespace Anymodule\Agentmodule\Application\Context;

use Anymodule\Agentmodule\Entity\Task;
use Anymodule\Agentmodule\Services\Workflows\Interface\Context;

class SimpleContext implements Context
{
    public function __construct(
        Task $task,
    )
    {
    }
}