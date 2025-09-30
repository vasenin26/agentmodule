<?php

namespace Anymodule\Agentmodule\Tools\Git;


use Anymodule\Agentmodule\Interface\Git\GitRepoProviderInterface;
use Anymodule\Agentmodule\Interface\Tools\ToolInterface;
use Anymodule\Agentmodule\Entity\ToolResult;

class ReadDir implements ToolInterface
{
    const NAME = 'git-read-dir';

    public function __construct(
        private GitRepoProviderInterface $repoProvider
    )
    {
    }

    //read directory contents from git repository
    public function execute(array $args): ?ToolResult
    {
        list('url' => $url, 'path' => $path) = $args;

        $repo = $this->repoProvider->getRepo($url);
        $fullPath = $repo->getRepositoryPath() . '/' . trim($path, '/');

        if (!is_dir($fullPath)) {
            return new ToolResult(false, 'Directory not found: ' . $path, ['code' => 'DIR_NOT_FOUND']);
        }

        $files = scandir($fullPath);

        if ($files === false) {
            return new ToolResult(false, 'Error reading directory: ' . $path, ['code' => 'READDIR_ERROR']);
        }

        // Remove . and .. entries
        $files = array_filter($files, function($file) {
            return $file !== '.' && $file !== '..';
        });

        if (empty($files)) {
            return new ToolResult(true, 'Directory is empty', ['path' => $path, 'files' => []]);
        }

        return new ToolResult(true, 'Git: readdir ok', ['path' => $path, 'files' => array_values($files)]);
    }

    public function getProps(): array
    {
        return [
            'type' => 'function',
            'function' => [
                'name' => $this->getName(),
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

    public function getName(): string
    {
        return self::NAME;
    }
}
