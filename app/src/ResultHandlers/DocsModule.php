<?php

namespace Anymodule\Agentmodule\ResultHandlers;

use Anymodule\Agentmodule\Entity\ProcessingResult;
use Anymodule\Agentmodule\Entity\Task;
use Anymodule\Agentmodule\Exception\AgentTaskStopped;
use Anymodule\Agentmodule\Interface\ProcessHandlerInterface;
use Anymodule\Agentmodule\Interface\Task\TaskApi;
use Ramsey\Uuid\UuidInterface;

class DocsModule implements ProcessHandlerInterface
{
    public function __construct(
        private TaskApi       $api,
        private UuidInterface $agentId,
        private Task          $task
    )
    {
    }

    /**
     * @throws AgentTaskStopped
     */
    public function handle(ProcessingResult $result): void
    {
        $response = $this->api->sendResult($this->agentId, $this->task->id, $result);

        if ($response->status === 'stopped') {
            throw new AgentTaskStopped();
        }
    }
}