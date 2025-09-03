<?php

namespace Anymodule\Agentmodule\Services\ApiService\Response\Admin;

use Anymodule\Agentmodule\Services\ApiService\Response\ResponseInterface;

readonly class StuckTasksDTO implements ResponseInterface
{
    public function __construct(
        public array $stuckTasks,
    )
    {
    }
}
