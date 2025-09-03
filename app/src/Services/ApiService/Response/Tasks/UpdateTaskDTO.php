<?php

namespace Anymodule\Agentmodule\Services\ApiService\Response\Tasks;

use Anymodule\Agentmodule\Services\ApiService\Response\ResponseInterface;

readonly class UpdateTaskDTO implements ResponseInterface
{
    public function __construct(
        public string $status,
        public string $message,
    )
    {
    }
}
