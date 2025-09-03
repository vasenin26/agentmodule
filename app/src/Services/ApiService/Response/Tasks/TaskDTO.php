<?php

namespace Anymodule\Agentmodule\Services\ApiService\Response\Tasks;

use Anymodule\Agentmodule\Services\ApiService\Response\ResponseInterface;

readonly class TaskDTO implements ResponseInterface
{
    public function __construct(
        public ?string $task_id,
        public ?string $status,
        public ?string $assigned_at,
        public ?string $message,
        public ?string $error,
    )
    {
    }
}