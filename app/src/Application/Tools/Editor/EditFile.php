<?php

namespace Anymodule\Agentmodule\Application\Tools\Editor;

use Anymodule\Agentmodule\Entity\ToolResult;
use Anymodule\Agentmodule\Interface\Git\GitRepoProviderInterface;
use Anymodule\Agentmodule\Interface\Tools\ToolInterface;

class EditFile implements ToolInterface
{
    const NAME = 'editor-edit-file';

    public function __construct(
        private GitRepoProviderInterface $repoProvider
    ) {
    }

    public function execute(array $args): ?ToolResult
    {
        try {
            ['url' => $url, 'path' => $path, 'content' => $content, 'create_if_not_exists' => $createIfNotExists] = $args + ['create_if_not_exists' => false];

            $repo = $this->repoProvider->getRepo($url);
            $repoPath = $repo->getRepositoryPath();

            if (!is_dir($repoPath)) {
                return new ToolResult(false, 'Repository not found', ['code' => 'REPO_NOT_FOUND']);
            }

            $fullFilePath = $repoPath . '/' . trim($path, '/');

            // Проверяем, что файл существует
            if (!file_exists($fullFilePath)) {
                if ($createIfNotExists) {
                    // Создаем директорию если она не существует
                    $dir = dirname($fullFilePath);
                    if (!is_dir($dir)) {
                        if (!mkdir($dir, 0755, true)) {
                            return new ToolResult(false, 'Failed to create directory: ' . dirname($path), ['code' => 'DIRECTORY_CREATE_FAILED']);
                        }
                    }
                    
                    // Создаем пустой файл
                    if (!touch($fullFilePath)) {
                        return new ToolResult(false, 'Failed to create file: ' . $path, ['code' => 'FILE_CREATE_FAILED']);
                    }
                } else {
                    return new ToolResult(false, 'File not found: ' . $path, ['code' => 'FILE_NOT_FOUND']);
                }
            }

            // Проверяем, что файл доступен для записи
            if (!is_writable($fullFilePath)) {
                return new ToolResult(false, 'File is not writable: ' . $path, ['code' => 'FILE_NOT_WRITABLE']);
            }

            // Определяем, был ли файл создан только что
            $isNewFile = $createIfNotExists && file_exists($fullFilePath) && filesize($fullFilePath) === 0;

            // Создаем резервную копию файла только если это не новый файл
            $backupPath = null;
            if (!$isNewFile) {
                $backupPath = $fullFilePath . '.backup.' . date('Y-m-d_H-i-s');
                if (!copy($fullFilePath, $backupPath)) {
                    return new ToolResult(false, 'Failed to create backup of file: ' . $path, ['code' => 'BACKUP_FAILED']);
                }
            }

            // Записываем новое содержимое в файл
            $result = file_put_contents($fullFilePath, $content);

            if ($result === false) {
                // Восстанавливаем файл из резервной копии в случае ошибки
                if ($backupPath) {
                    copy($backupPath, $fullFilePath);
                    unlink($backupPath);
                } else {
                    // Если это был новый файл, удаляем его
                    unlink($fullFilePath);
                }
                
                return new ToolResult(false, 'Failed to write content to file: ' . $path, ['code' => 'WRITE_FAILED']);
            }

            // Удаляем резервную копию после успешной записи
            if ($backupPath) {
                unlink($backupPath);
            }

            return new ToolResult(true, $isNewFile ? 'File created successfully' : 'File updated successfully', [
                'file_path' => $path,
                'file_created' => $isNewFile,
                'bytes_written' => $result,
                'content_length' => strlen($content),
            ]);

        } catch (\Throwable $e) {
            return new ToolResult(false, 'Failed to edit file: ' . $e->getMessage(), ['code' => 'EDIT_ERROR', 'exception' => get_class($e)]);
        }
    }


    public function getProps(): array
    {
        return [
            'type' => 'function',
            'function' => [
                'name' => $this->getName(),
                'description' => 'Replaces the entire content of the file with new content. This operation is not recommended for large files due to performance and memory limitations.',
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
                        'content' => [
                            'type' => 'string',
                            'description' => 'New content to write to the file',
                        ],
                        'create_if_not_exists' => [
                            'type' => 'boolean',
                            'description' => 'Create file if it does not exist. If true, creates the file and any necessary directories.',
                            'default' => false
                        ]
                    ],
                    'required' => ['url', 'path', 'content'],
                ]
            ]
        ];
    }

    public function getName(): string
    {
        return self::NAME;
    }
}
