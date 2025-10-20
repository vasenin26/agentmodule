<?php

namespace Anymodule\Agentmodule\Services\ApiService\Response\Tasks;

use Anymodule\Agentmodule\Services\ApiService\Response\ResponseInterface;

readonly class CreateSubtaskDTO implements ResponseInterface
{
    public function __construct(
        public int $id,
    )
    {
    }
}
