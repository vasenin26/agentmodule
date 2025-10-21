<?php

namespace Anymodule\Agentmodule\Interface;

use Anymodule\Agentmodule\Application\Tools\Tasks\TaskStorageInterface;
use Anymodule\Agentmodule\Entity\Context;

interface TaskStorageProviderInterface
{
    public function getTaskStorage(?int $id): TaskStorageInterface;

    public function createStorageFromContext(Context $context): TaskStorageInterface;
}