<?php

namespace Anymodule\Agentmodule\Application\Tools\Git\RepoManagement;

use Anymodule\Agentmodule\Entity\ToolResult;
use Anymodule\Agentmodule\Interface\Git\GitRepoProviderInterface;
use Anymodule\Agentmodule\Interface\Tools\FileModifyingToolInterface;

class UnstageFile implements FileModifyingToolInterface
{
    const NAME = 'git-unstage-file';

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

            $normalizedPath = trim($path, '/');

            // Удаляем файл из индекса
            $result = $repo->run(['reset', 'HEAD', '--', $normalizedPath]);
            if (!$result->isOk()) {
                throw new \Exception('Unstage failed: ' . $result->getErrorOutput());
            }

            return new ToolResult(true, 'Git: unstage ok', [
                'file_path' => $normalizedPath,
            ]);

        } catch (\Throwable $e) {
            return new ToolResult(false, 'Failed to unstage file: ' . $e->getMessage(), ['code' => 'UNSTAGE_ERROR', 'exception' => get_class($e)]);
        }
    }

    public function getProps(): array
    {
        return [
            'type' => 'function',
            'function' => [
                'name' => $this->getName(),
                'description' => 'Remove file from git index (git reset HEAD)',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'url' => [
                            'type' => 'string',
                            'description' => 'Git repository URL',
                        ],
                        'path' => [
                            'type' => 'string',
                            'description' => 'Path to file to remove from git index',
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
