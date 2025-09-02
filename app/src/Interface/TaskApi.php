<?php

namespace Anymodule\Agentmodule\Interface;

use Anymodule\Agentmodule\Entity\LLMResult;
use Anymodule\Agentmodule\Entity\Task;

interface TaskApi
{
    public function getTask(): ?Task;

    public function sendResult(int $id, LLMResult $result);
}