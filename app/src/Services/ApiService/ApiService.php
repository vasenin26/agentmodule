<?php

namespace Anymodule\Agentmodule\Services\ApiService;

use Anymodule\Agentmodule\Entity\LLMResult;
use Anymodule\Agentmodule\Entity\Task;
use Anymodule\Agentmodule\Interface\PageApi;
use Anymodule\Agentmodule\Interface\TaskApi;
use Anymodule\Agentmodule\Services\ApiService\Request\Tasks\GetTask;

class ApiService implements TaskApi, PageApi
{
    private ApiClient $api;

    public function __construct(string $host)
    {
        $this->api = new ApiClient($host);
    }

    public function getTask(): ?Task
    {
        $request = new GetTask();
        $taskData = $request->exec($this->api);

        return null;
    }

    public function sendResult(int $id, LLMResult $result)
    {

    }
}