<?php

namespace Anymodule\Agentmodule\Tools\Utils;

use Anymodule\Agentmodule\Interface\Tools\ToolInterface;
use Anymodule\Agentmodule\Entity\ToolResult;

class AddFileToList implements ToolInterface
{
    const NAME = 'add-file-to-list';

    private array $list;

    public function __construct(array &$list)
    {
        $this->list = &$list;
    }

    public function execute(array $args): ?ToolResult
    {
        $this->list[] = [
            'url' => $args['url'],
            'path' => $args['path'],
            'description' => $args['description'] ?? '',
        ];

        return new ToolResult(true, 'File added to list', [
            'url' => $args['url'],
            'path' => $args['path'],
        ]);
    }

    public function getProps(): array
    {
        return [
            'type' => 'function',
            'function' => [
                'name' => $this->getName(),
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

    public function getName(): string
    {
        return self::NAME;
    }
}