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
                'description' => 'Add one or multiple tasks into the agent’s private memory. '
                    . 'Use this to break down the user’s request into steps and track them internally. '
                    . 'IDs are generated automatically and are not visible to the user.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'title' => [
                            'type' => 'string',
                            'description' => 'The title of a single internal task to add. '
                                . 'Keep it short and action-oriented (e.g., "Collect materials").',
                            'minLength' => 1
                        ],
                        'titles' => [
                            'type' => 'array',
                            'items' => [
                                'type' => 'string',
                                'minLength' => 1
                            ],
                            'minItems' => 1,
                            'description' => 'A list of internal task titles to add in bulk. '
                                . 'Each entry should represent one step of the plan.',
                        ],
                    ],
                    'additionalProperties' => false,
                ]
            ]
        ];
    }
}


