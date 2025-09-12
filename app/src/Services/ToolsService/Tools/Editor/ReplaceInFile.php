<?php

namespace Anymodule\Agentmodule\Services\ToolsService\Tools\Editor;

use Anymodule\Agentmodule\Interface\Git\GitRepoProviderInterface;
use Anymodule\Agentmodule\Interface\Tools\ToolInterface;

class ReplaceInFile implements ToolInterface
{
    public function __construct(
        private GitRepoProviderInterface $repoProvider
    ) {
    }

    public function execute(array $args): ?string
    {
        try {
            ['url' => $url, 'path' => $path, 'pattern' => $pattern, 'replacement' => $replacement] = $args;

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

            // Читаем содержимое файла
            $content = file_get_contents($fullFilePath);
            if ($content === false) {
                return json_encode([
                    'success' => false,
                    'error' => 'Failed to read file: ' . $path,
                    'code' => 'READ_FAILED',
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

            // Выполняем замену
            $originalContent = $content;
            $newContent = preg_replace($pattern, $replacement, $content);

            // Проверяем, была ли выполнена замена
            if ($originalContent === $newContent) {
                // Удаляем резервную копию, так как изменений не было
                unlink($backupPath);
                
                return json_encode([
                    'success' => true,
                    'message' => 'No matches found for pattern',
                    'data' => [
                        'file_path' => $path,
                        'pattern' => $pattern,
                        'replacements_made' => 0
                    ]
                ]);
            }

            // Записываем новое содержимое в файл
            $result = file_put_contents($fullFilePath, $newContent);

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

            // Подсчитываем количество замен
            $replacementsCount = preg_match_all($pattern, $originalContent);

            // Удаляем резервную копию после успешной записи
            unlink($backupPath);

            return json_encode([
                'success' => true,
                'message' => 'File updated successfully',
                'data' => [
                    'file_path' => $path,
                    'pattern' => $pattern,
                    'replacement' => $replacement,
                    'replacements_made' => $replacementsCount,
                    'bytes_written' => $result,
                    'content_length' => strlen($newContent)
                ]
            ]);

        } catch (\Exception $e) {
            return json_encode([
                'success' => false,
                'error' => 'Failed to replace in file: ' . $e->getMessage(),
                'code' => 'REPLACE_ERROR',
            ]);
        }
    }

    public function getProps($name): array
    {
        return [
            'type' => 'function',
            'function' => [
                'name' => $name,
                'description' => 'Replace text in file using regex pattern',
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
                        'pattern' => [
                            'type' => 'string',
                            'description' => 'Regex pattern to search for',
                        ],
                        'replacement' => [
                            'type' => 'string',
                            'description' => 'Replacement text',
                        ]
                    ],
                    'required' => ['url', 'path', 'pattern', 'replacement'],
                ]
            ]
        ];
    }
}
