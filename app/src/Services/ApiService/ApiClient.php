<?php

namespace Anymodule\Agentmodule\Services\ApiService;

use Anymodule\Agentmodule\Entity\LLMResult;
use Anymodule\Agentmodule\Entity\Task;
use Anymodule\Agentmodule\Interface\PageApi;
use Anymodule\Agentmodule\Interface\TaskApi;

class ApiClient implements TaskApi, PageApi
{

    public function getTask(): ?Task
    {
        return null;
    }

    public function sendResult(int $id, LLMResult $result)
    {

    }
}