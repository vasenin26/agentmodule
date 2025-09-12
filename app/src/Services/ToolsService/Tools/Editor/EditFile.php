<?php

namespace Anymodule\Agentmodule\Services\ToolsService\Tools\Editor;

use Anymodule\Agentmodule\Interface\Git\GitRepoProviderInterface;
use Anymodule\Agentmodule\Interface\Tools\ToolInterface;

class EditFile implements ToolInterface
{
    public function __construct(
        private GitRepoProviderInterface $repoProvider
    ) {
    }

    public function execute(array $args): ?string
    {
        try {
            ['url' => $url, 'path' => $path, 'content' => $content] = $args;

            $repo = $this->repoProvider->getRepo($url);
            $repoPath = $repo->getRepositoryPath();

            if (!is_dir($repoPath)) {
                return json_encode([
                    'success' => false,
                    'error' => 'Repository not found',
                    'code' => 'REPO_NOT_FOUND',
                ]);
            }

            $fullFilePath = $repoPath . '/' . trim($path, '/');

            // Проверяем, что файл существует
            if (!file_exists($fullFilePath)) {
                return json_encode([
                    'success' => false,
                    'error' => 'File not found: ' . $path,
                    'code' => 'FILE_NOT_FOUND',
                ]);
            }

            // Проверяем, что файл доступен для записи
            if (!is_writable($fullFilePath)) {
                return json_encode([
                    'success' => false,
                    'error' => 'File is not writable: ' . $path,
                    'code' => 'FILE_NOT_WRITABLE',
                ]);
            }

            // Создаем резервную копию файла
            $backupPath = $fullFilePath . '.backup.' . date('Y-m-d_H-i-s');
            if (!copy($fullFilePath, $backupPath)) {
                return json_encode([
                    'success' => false,
                    'error' => 'Failed to create backup of file: ' . $path,
                    'code' => 'BACKUP_FAILED',
                ]);
            }

            // Записываем новое содержимое в файл
            $result = file_put_contents($fullFilePath, $content);

            if ($result === false) {
                // Восстанавливаем файл из резервной копии в случае ошибки
                copy($backupPath, $fullFilePath);
                unlink($backupPath);
                
                return json_encode([
                    'success' => false,
                    'error' => 'Failed to write content to file: ' . $path,
                    'code' => 'WRITE_FAILED',
                ]);
            }

            // Удаляем резервную копию после успешной записи
            unlink($backupPath);

            return json_encode([
                'success' => true,
                'message' => 'File updated successfully',
                'data' => [
                    'file_path' => $path,
                    'bytes_written' => $result,
                    'content_length' => strlen($content)
                ]
            ]);

        } catch (\Exception $e) {
            return json_encode([
                'success' => false,
                'error' => 'Failed to edit file: ' . $e->getMessage(),
                'code' => 'EDIT_ERROR',
            ]);
        }
    }


    public function getProps($name): array
    {
        return [
            'type' => 'function',
            'function' => [
                'name' => $name,
                'description' => 'Edit file in repository',
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
                        ]
                    ],
                    'required' => ['url', 'path', 'content'],
                ]
            ]
        ];
    }
}
