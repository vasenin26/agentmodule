<?php

namespace Anymodule\Agentmodule\Services;

use Anymodule\Agentmodule\Application\Tools\Tasks\TasksStorage;
use Anymodule\Agentmodule\Interface\TaskStorageProviderInterface;

class TaskStorageProvider implements TaskStorageProviderInterface
{
    private $storages = [];

    public function getTaskStorage(?int $id): TasksStorage
    {
        if (is_null($id)) {
            return new TasksStorage();
        }

        return $this->storages[$id] ?? $this->storages[$id] = new TasksStorage();
    }
}