<?php

namespace Anymodule\Agentmodule\Application;

use Anymodule\Agentmodule\Application\ResultHandlers\DocsModule;
use Anymodule\Agentmodule\Interface\ProcessHandlerInterface;
use Anymodule\Agentmodule\Interface\SubtaskCreatorInterface;
use Anymodule\Agentmodule\Interface\Task\TaskApiInterface;
use Ramsey\Uuid\UuidInterface;

class SubtaskCreator implements SubtaskCreatorInterface
{
    public function __construct(
        private int              $taskId,
        private UuidInterface    $agentUuid,
        private TaskApiInterface $taskApi,
    )
    {
    }

    public function createSubtask(string $subtaskType): ProcessHandlerInterface
    {
        $subtaskId = $this->taskApi->createSubtask($this->agentUuid, $this->taskId, $subtaskType);

        return new DocsModule(
            $this->taskApi,
            $this->agentUuid,
            $subtaskId,
        );
    }
}