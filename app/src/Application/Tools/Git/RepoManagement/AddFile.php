<?php

namespace Anymodule\Agentmodule\Application\Tools\Git\RepoManagement;

use Anymodule\Agentmodule\Entity\ToolResult;
use Anymodule\Agentmodule\Interface\Git\GitRepoProviderInterface;
use Anymodule\Agentmodule\Interface\Tools\FileModifyingToolInterface;

class AddFile implements FileModifyingToolInterface
{
    const NAME = 'git-add-file';

    public function __construct(
        private GitRepoProviderInterface $repoProvider
    ) {
    }

    public function execute(array $args): ?ToolResult
    {
        try {
            ['url' => $url, 'paths' => $paths] = $args;

            if (empty($url) || !is_string($url)) {
                return new ToolResult(false, 'URL is required and must be a non-empty string', ['code' => 'INVALID_URL']);
            }

            if (empty($paths)) {
                return new ToolResult(false, 'Paths are required and must be a non-empty array', ['code' => 'INVALID_PATHS']);
            }

            // Поддерживаем как массив путей, так и одиночный путь для обратной совместимости
            if (is_string($paths)) {
                $paths = [$paths];
            }

            if (!is_array($paths)) {
                return new ToolResult(false, 'Paths must be an array of strings or a single string', ['code' => 'INVALID_PATHS_TYPE']);
            }

            $repo = $this->repoProvider->getRepo($url);
            $repoPath = $repo->getRepositoryPath();

            $addedFiles = [];
            $errors = [];

            foreach ($paths as $path) {
                if (empty($path) || !is_string($path)) {
                    $errors[] = "Invalid path: " . (is_string($path) ? $path : gettype($path));
                    continue;
                }

                $normalizedPath = trim($path, '/');
                $fullPath = $repoPath . '/' . $normalizedPath;

                // Проверяем, что файл существует
                if (!file_exists($fullPath)) {
                    $errors[] = "File not found: " . $path;
                    continue;
                }

                try {
                    // Добавляем файл в индекс
                    $repo->addFile($normalizedPath);
                    $addedFiles[] = $normalizedPath;
                } catch (\Throwable $e) {
                    $errors[] = "Failed to add file '{$path}': " . $e->getMessage();
                }
            }

            if (empty($addedFiles)) {
                return new ToolResult(false, 'No files were added. Errors: ' . implode('; ', $errors), ['code' => 'NO_FILES_ADDED', 'errors' => $errors]);
            }

            $message = 'Git: add ok';
            if (!empty($errors)) {
                $message .= '. Some files failed: ' . implode('; ', $errors);
            }

            return new ToolResult(true, $message, [
                'added_files' => $addedFiles,
                'errors' => $errors,
                'total_added' => count($addedFiles),
                'total_failed' => count($errors)
            ]);

        } catch (\Throwable $e) {
            return new ToolResult(false, 'Failed to add files to git index: ' . $e->getMessage(), ['code' => 'ADD_ERROR', 'exception' => get_class($e)]);
        }
    }

    public function getProps(): array
    {
        return [
            'type' => 'function',
            'function' => [
                'name' => $this->getName(),
                'description' => 'Add one or multiple files to git index (git add). Supports both single file path and array of file paths.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'url' => [
                            'type' => 'string',
                            'description' => 'Git repository URL',
                        ],
                        'paths' => [
                            'oneOf' => [
                                [
                                    'type' => 'string',
                                    'description' => 'Single file path to add to git index',
                                ],
                                [
                                    'type' => 'array',
                                    'items' => [
                                        'type' => 'string'
                                    ],
                                    'description' => 'Array of file paths to add to git index',
                                ]
                            ]
                        ]
                    ],
                    'required' => ['url', 'paths'],
                ]
            ]
        ];
    }

    public function getName(): string
    {
        return self::NAME;
    }
}
