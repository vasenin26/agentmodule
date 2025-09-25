<?php

namespace Anymodule\Agentmodule\Tools\Utils;

use Anymodule\Agentmodule\Interface\Tools\ToolInterface;

class AddFileToList implements ToolInterface
{
    private array $list;

    public function __construct(array &$list)
    {
        $this->list = &$list;
    }

    public function execute(array $args): ?string
    {
        $this->list[] = [
            'url' => $args['url'],
            'path' => $args['path'],
            'description' => $args['description'] ?? '',
        ];

        return 'File added to list';
    }

    public function getProps($name): array
    {
        return [
            'type' => 'function',
            'function' => [
                'name' => $name,
                'description' => 'Store file to list',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'url' => [
                            'type' => 'string',
                            'description' => 'Git repository url',
                        ],
                        'path' => [
                            'type' => 'string',
                            'description' => 'Path to file',
                        ],
                        'description' => [
                            'type' => 'string',
                            'description' => 'А description of what information the file contains and how it can be useful for solving the problem',
                        ]
                    ],
                    'required' => ['url', 'path'],
                ]
            ]
        ];
    }
}