<?php

namespace Anymodule\Agentmodule\Services\ApiService\Response\Tasks;

use Anymodule\Agentmodule\Services\ApiService\Response\ResponseInterface;

readonly class TaskDTO implements ResponseInterface
{
    public function __construct(
        public int $task_id,
        public int $project_id,
        public array $messages,
    )
    {
    }
}