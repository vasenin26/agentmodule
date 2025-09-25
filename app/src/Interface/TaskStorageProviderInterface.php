<?php

namespace Anymodule\Agentmodule\Interface;

use Anymodule\Agentmodule\Tools\Tasks\TasksStorage;

interface TaskStorageProviderInterface
{
    public function getTaskStorage(int $id): TasksStorage;
}