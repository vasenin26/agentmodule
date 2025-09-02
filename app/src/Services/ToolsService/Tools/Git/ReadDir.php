<?php

namespace Anymodule\Agentmodule\Services\ToolsService\Tools\Git;


use Anymodule\Agentmodule\Interface\GitRepoProviderInterface;
use Anymodule\Agentmodule\Interface\ToolInterface;

class ReadDir implements ToolInterface
{

    public function __construct(
        private GitRepoProviderInterface $repoProvider
    )
    {
    }

    //read directory contents from git repository
    public function execute(array $args): ?string
    {
        list('url' => $url, 'path' => $path) = $args;

        $repo = $this->repoProvider->getRepo($url);
        $fullPath = $repo->getRepositoryPath() . '/' . trim($path, '/');

        if (!is_dir($fullPath)) {
            return "Directory not found: $path";
        }

        $files = scandir($fullPath);

        if ($files === false) {
            return "Error reading directory: $path";
        }

        // Remove . and .. entries
        $files = array_filter($files, function($file) {
            return $file !== '.' && $file !== '..';
        });

        if (empty($files)) {
            return "Directory is empty: $path";
        }

        return implode("\n", $files);
    }

    public function getProps($name): array
    {
        return [
            'type' => 'function',
            'function' => [
                'name' => $name,
                'description' => 'Read directory contents from repository',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'url' => [
                            'type' => 'string',
                            'description' => 'Git repository url',
                        ],
                        'path' => [
                            'type' => 'string',
                            'description' => 'Path to directory',
                        ]
                    ],
                    'required' => ['url', 'path'],
                ]
            ]
        ];
    }
}
