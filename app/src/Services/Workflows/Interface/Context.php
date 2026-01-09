<?php

namespace Anymodule\Agentmodule\Services\Workflows\Interface;

use Anymodule\Agentmodule\Entity\Task;
use Vasenin26\Conversation\Interface\Conversation;

interface Context
{
    public function getProjectId(): int;

    public function getTask(): Task;

    public function getConversation(): Conversation;
}