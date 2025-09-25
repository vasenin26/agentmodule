<?php

namespace Anymodule\Agentmodule\Interface\Task;

use Anymodule\Agentmodule\Entity\ProcessingResult;
use Anymodule\Agentmodule\Entity\Task;
use Ramsey\Uuid\UuidInterface;

interface TaskApi
{
    public function getTask(UuidInterface $agentId): ?Task;

    public function sendResult(UuidInterface $agentId, int $taskId, ProcessingResult $result);
}