<?php

namespace Anymodule\Agentmodule\Interface;

interface SubtaskCreatorInterface
{
    public function createSubtask(string $subtaskType): ProcessHandlerInterface;
}
