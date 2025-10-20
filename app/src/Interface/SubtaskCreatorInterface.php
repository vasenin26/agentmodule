<?php

namespace Anymodule\Agentmodule\Interface;

use Anymodule\Agentmodule\Interface\ProcessHandlerInterface;

interface SubtaskCreatorInterface
{
    public function createSubtask(): ProcessHandlerInterface;
}
