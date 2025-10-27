<?php

namespace Anymodule\Agentmodule\Services;

use Anymodule\Agentmodule\Application\Tools\Tasks\TaskStorageInterface;
use Anymodule\Agentmodule\Entity\Context;
use Anymodule\Agentmodule\Interface\Storage\TaskStorageProviderInterface;
use Anymodule\Agentmodule\Services\TaskStorage\MemoStorage;
use Anymodule\Agentmodule\Services\TaskStorage\TasksStorage;

class TaskStorageProvider implements TaskStorageProviderInterface
{
    private $storages = [];

    public function getTaskStorage(?int $id): TaskStorageInterface
    {
        if (is_null($id)) {
            return new TasksStorage();
        }

        return $this->storages[$id] ?? $this->storages[$id] = new TasksStorage();
    }

    public function createStorageFromContext(Context $context): TaskStorageInterface
    {
        return new MemoStorage($context->tasks);
    }
}