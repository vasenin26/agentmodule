<?php

namespace Anymodule\Agentmodule\Services\Workflows\Interface;

use Anymodule\Agentmodule\Entity\ContextConversation;
use Anymodule\Agentmodule\Entity\Task;

interface Context
{
    public function getProjectId(): int;

    public function getTask(): Task;

    public function getContextConversation(): ContextConversation;
}