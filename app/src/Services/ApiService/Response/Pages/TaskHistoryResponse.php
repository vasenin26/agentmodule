<?php

namespace Anymodule\Agentmodule\Services\ApiService\Response\Pages;

use Anymodule\Agentmodule\Services\ApiService\Response\ResponseInterface;

readonly class TaskHistoryResponse implements ResponseInterface
{
    /**
     * @param TaskHistoryDTO[] $tasks
     */
    public function __construct(
        private array $tasks
    ) {
    }

    /**
     * @return TaskHistoryDTO[]
     */
    public function getTasks(): array
    {
        return $this->tasks;
    }
}