<?php

namespace Anymodule\Agentmodule\Services\ToolsService\Tools\Tasks;

use Anymodule\Agentmodule\Interface\Tools\ToolInterface;

class ListTasks implements ToolInterface
{
    public function __construct(private TasksStorage $storage)
    {
    }

    public function execute(array $args): ?string
    {
        $tasks = $this->storage->list();
        return json_encode($tasks, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    public function getProps($name): array
    {
        return [
            'type' => 'function',
            'function' => [
                'name' => $name,
                'description' => 'Return all tasks with their IDs, titles and completion status.'
            ]
        ];
    }
}


