<?php

namespace Anymodule\Agentmodule\Interface;

use Anymodule\Agentmodule\Services\ToolsService\Tools\Tasks\TasksStorage;

interface TaskStorageProviderInterface
{
    public function getTaskStorage(int $id): TasksStorage;
}