<?php

namespace Anymodule\Agentmodule\Services\ApiService\Response\Tasks;

use Anymodule\Agentmodule\Services\ApiService\Response\ResponseInterface;

readonly class TaskDetailsDTO implements ResponseInterface
{
    public function __construct(
        public int $id,
        public int $projectId,
        public string $agentId,
        public string $status,
    )
    {
    }
}
