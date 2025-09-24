<?php

namespace Anymodule\Agentmodule\Services;

use Anymodule\Agentmodule\Interface\TaskStorageProviderInterface;
use Anymodule\Agentmodule\Services\ToolsService\Tools\Tasks\TasksStorage;

class TaskStorageProvider implements TaskStorageProviderInterface
{
    private $storages = [];

    public function getTaskStorage(int $id): TasksStorage
    {
        return $this->storages[$id] ?? $this->storages[$id] = new TasksStorage();
    }
}