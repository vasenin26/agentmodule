<?php

namespace Anymodule\Agentmodule\Services\ToolsService\Tools\Tasks;

use Anymodule\Agentmodule\Interface\Tools\ToolInterface;

class AddTasks implements ToolInterface
{
    public function __construct(private TasksStorage $storage)
    {
    }

    public function execute(array $args): ?string
    {
        $items = [];
        if (isset($args['title']) && is_string($args['title'])) {
            $items[] = $args['title'];
        }
        if (isset($args['titles']) && is_array($args['titles'])) {
            foreach ($args['titles'] as $t) {
                if (is_string($t)) {
                    $items[] = $t;
                }
            }
        }
        if (empty($items)) {
            return json_encode(['error' => 'No tasks provided'], JSON_UNESCAPED_UNICODE);
        }
        $created = $this->storage->addMany($items);
        return json_encode($created, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    public function getProps($name): array
    {
        return [
            'type' => 'function',
            'function' => [
                'name' => $name,
                'description' => 'Add one or multiple tasks. IDs are generated automatically.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'title' => [
                            'type' => 'string',
                            'description' => 'Title of a single task to add.',
                            'minLength' => 1
                        ],
                        'titles' => [
                            'type' => 'array',
                            'items' => [
                                'type' => 'string',
                                'minLength' => 1
                            ],
                            'minItems' => 1,
                            'description' => 'List of task titles to add in bulk.',
                        ],
                    ],
                    'additionalProperties' => false,
                ]
            ]
        ];
    }
}


