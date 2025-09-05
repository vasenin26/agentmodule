<?php

namespace Anymodule\Agentmodule\Services\ApiService\Response\Pages;

use Anymodule\Agentmodule\Services\ApiService\Response\ResponseInterface;

readonly class TaskHistoryDTO implements ResponseInterface
{
    public function __construct(
        public int $id,
        public string $status,
        public string $created_at,
        public string $updated_at,
        public ?CreatorDTO $creator = null,
        public ?TechplaneDTO $techplane = null,
    )
    {
    }
}

readonly class CreatorDTO implements ResponseInterface
{
    public function __construct(
        public int $id,
        public string $name,
        public string $email,
    )
    {
    }
}

readonly class TechplaneDTO implements ResponseInterface
{
    public function __construct(
        public int $id,
        public string $title,
    )
    {
    }
}
