<?php

namespace Anymodule\Agentmodule\Services\ApiService\Response\Admin;

use Anymodule\Agentmodule\Services\ApiService\Response\ResponseInterface;

readonly class ResetStuckTasksDTO implements ResponseInterface
{
    public function __construct(
        public int $resetCount,
    )
    {
    }
}
