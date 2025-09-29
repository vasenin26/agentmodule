<?php

namespace Anymodule\Agentmodule\Tools\Tasks;

use Anymodule\Agentmodule\Interface\Tools\ToolInterface;

class CompleteTask implements ToolInterface
{
    const NAME = 'tasks-complete';

    public function __construct(private TasksStorage $storage)
    {
    }

    public function execute(array $args): ?string
    {
        $id = isset($args['id']) ? (int)$args['id'] : 0;
        if ($id <= 0) {
            return json_encode(['error' => 'Invalid id'], JSON_UNESCAPED_UNICODE);
        }
        $task = $this->storage->complete($id);
        if ($task === null) {
            return json_encode(['error' => 'Task not found'], JSON_UNESCAPED_UNICODE);
        }
        
        $stats = $this->storage->getStats();
        
        $result = [
            'task' => $task,
            'stats' => $stats
        ];
        
        return json_encode($result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
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


