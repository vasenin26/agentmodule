<?php

namespace Anymodule\Agentmodule\Interface;

use Anymodule\Agentmodule\Entity\LLMResult;
use Anymodule\Agentmodule\Entity\Task;
use Ramsey\Uuid\UuidInterface;

interface TaskApi
{
    public function getTask(UuidInterface $agentId): ?Task;

    public function sendResult(UuidInterface $agentId, int $taskId, LLMResult $result);
}