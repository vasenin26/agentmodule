<?php

namespace Anymodule\Agentmodule\Utils;

use Anymodule\Agentmodule\Entity\LLMResult;
use Anymodule\Agentmodule\Entity\Task;
use Anymodule\Agentmodule\Interface\PageApi;
use Anymodule\Agentmodule\Interface\TaskApi;
use Ramsey\Uuid\UuidInterface;

class FakeApi implements TaskApi, PageApi
{

    public function getTask(UuidInterface $agentId): ?Task
    {
        return new Task(
            id: 1,
            messages: [
                [
                    'role' => 'user',
                    'content' => 'hello world',
                ]
            ]
        );
    }

    public function sendResult(UuidInterface $agentId, int $taskId, LLMResult $result)
    {
        var_dump($result);
    }
}