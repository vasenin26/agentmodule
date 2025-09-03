<?php

namespace Anymodule\Agentmodule\Services\ApiService\Response\Admin;

use Anymodule\Agentmodule\Services\ApiService\Response\ResponseInterface;

readonly class AgentStatsDTO implements ResponseInterface
{
    public function __construct(
        public int $waiting,
        public int $processing,
        public int $completed,
        public int $failed,
    )
    {
    }
}
