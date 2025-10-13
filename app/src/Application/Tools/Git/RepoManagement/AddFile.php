<?php

namespace Anymodule\Agentmodule\Application\Tools\Git\RepoManagement;

use Anymodule\Agentmodule\Entity\ToolResult;
use Anymodule\Agentmodule\Interface\Git\GitRepoProviderInterface;
use Anymodule\Agentmodule\Interface\Tools\ToolInterface;

class AddFile implements ToolInterface
{
    const NAME = 'git-add-file';

    public function __construct(
        private GitRepoProviderInterface $repoProvider
    ) {
    }

    public function execute(array $args): ?ToolResult
    {
        try {
            ['url' => $url, 'path' => $path] = $args;

            if (empty($url) || !is_string($url)) {
                return new ToolResult(false, 'URL is required and must be a non-empty string', ['code' => 'INVALID_URL']);
            }

            if (empty($path) || !is_string($path)) {
                return new ToolResult(false, 'Path is required and must be a non-empty string', ['code' => 'INVALID_PATH']);
            }

            $repo = $this->repoProvider->getRepo($url);
            $repoPath = $repo->getRepositoryPath();

            $normalizedPath = trim($path, '/');
            $fullPath = $repoPath . '/' . $normalizedPath;

            // Проверяем, что файл существует
            if (!file_exists($fullPath)) {
                return new ToolResult(false, 'File not found: ' . $path, ['code' => 'FILE_NOT_FOUND']);
            }

            // Добавляем файл в индекс
            $repo->addFile($normalizedPath);

            return new ToolResult(true, 'Git: add ok', [
                'file_path' => $normalizedPath,
            ]);

        } catch (\Throwable $e) {
            return new ToolResult(false, 'Failed to add file to git index: ' . $e->getMessage(), ['code' => 'ADD_ERROR', 'exception' => get_class($e)]);
        }
    }

    public function getProps(): array
    {
        return [
            'type' => 'function',
            'function' => [
                'name' => $this->getName(),
                'description' => 'Add file to git index (git add)',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'url' => [
                            'type' => 'string',
                            'description' => 'Git repository URL',
                        ],
                        'path' => [
                            'type' => 'string',
                            'description' => 'Path to file to add to git index',
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
