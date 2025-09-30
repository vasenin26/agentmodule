<?php

namespace Anymodule\Agentmodule\Tools\Tasks;

use Anymodule\Agentmodule\Interface\Tools\ToolInterface;
use Anymodule\Agentmodule\Entity\ToolResult;

class CompleteTask implements ToolInterface
{
    const NAME = 'tasks-complete';

    public function __construct(private TasksStorage $storage)
    {
    }

    public function execute(array $args): ?ToolResult
    {
        $id = isset($args['id']) ? (int)$args['id'] : 0;
        if ($id <= 0) {
            return new ToolResult(false, 'Invalid id', ['code' => 'INVALID_ID']);
        }
        $task = $this->storage->complete($id);
        if ($task === null) {
            return new ToolResult(false, 'Task not found', ['code' => 'TASK_NOT_FOUND']);
        }
        
        $stats = $this->storage->getStats();
        
        $payload = [
            'task' => $task,
            'stats' => $stats,
        ];
        return new ToolResult(true, 'Task completed', $payload);
    }

    public function getProps(): array
    {
        return [
            'type' => 'function',
            'function' => [
                'name' => $this->getName(),
                'description' => 'Mark an internal task as completed by its ID. '
                    . 'Tasks and IDs belong to the agent’s private memory only. '
                    . 'Never reveal these IDs to the user, just use them to track your progress.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'id' => [
                            'type' => 'integer',
                            'description' => 'The ID of the internal task to complete. Must be a valid ID from memory.',
                            'minimum' => 1
                        ],
                    ],
                    'required' => ['id'],
                    'additionalProperties' => false,
                ]
            ]
        ];
    }

    public function getName(): string
    {
        return self::NAME;
    }
}


