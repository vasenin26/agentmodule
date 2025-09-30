<?php

namespace Anymodule\Agentmodule\Tools\Tasks;

use Anymodule\Agentmodule\Interface\Tools\ToolInterface;
use Anymodule\Agentmodule\Entity\ToolResult;

class ListTasks implements ToolInterface
{
    const NAME = 'get-task-list';

    public function __construct(private TasksStorage $storage)
    {
    }

    public function execute(array $args): ?ToolResult
    {
        $tasks = $this->storage->list();
        $stats = $this->storage->getStats();
        
        $payload = [
            'tasks' => $tasks,
            'stats' => $stats,
        ];
        return new ToolResult(true, 'Tasks listed', $payload);
    }

    public function getProps(): array
    {
        return [
            'type' => 'function',
            'function' => [
                'name' => $this->getName(),
                'description' => 'Return the full list of internal tasks with their IDs, titles, and completion status. '
                    . 'This is the agent’s private memory, not visible to the user. '
                    . 'Use this only to recall or review your own plan of work.',
            ]
        ];
    }

    public function getName(): string
    {
        return self::NAME;
    }
}


