<?php

namespace Anymodule\Agentmodule\Application\Tools\Editor;

use Anymodule\Agentmodule\Entity\ToolResult;
use Anymodule\Agentmodule\Interface\Git\GitRepoProviderInterface;
use Anymodule\Agentmodule\Interface\Tools\ToolInterface;

class DeleteLines implements ToolInterface
{
    const NAME = 'editor-delete-lines';

    public function __construct(
        private GitRepoProviderInterface $repoProvider
    )
    {
    }

    public function execute(array $args): ?ToolResult
    {
        try {
            ['url' => $url, 'path' => $path, 'line_numbers' => $lineNumbers] = $args;

            $repo = $this->repoProvider->getRepo($url);
            $repoPath = $repo->getRepositoryPath();

            if (!is_dir($repoPath)) {
                return new ToolResult(false, 'Repository not found', ['code' => 'REPO_NOT_FOUND']);
            }

            $fullFilePath = $repoPath . '/' . trim($path, '/');

            // Проверяем, что файл существует
            if (!file_exists($fullFilePath)) {
                return new ToolResult(false, 'File not found: ' . $path, ['code' => 'FILE_NOT_FOUND']);
            }

            // Проверяем, что файл доступен для записи
            if (!is_writable($fullFilePath)) {
                return new ToolResult(false, 'File is not writable: ' . $path, ['code' => 'FILE_NOT_WRITABLE']);
            }

            // Читаем содержимое файла
            $originalContent = file_get_contents($fullFilePath);
            if ($originalContent === false) {
                return new ToolResult(false, 'Failed to read file: ' . $path, ['code' => 'READ_FAILED']);
            }

            // Создаем резервную копию файла
            $backupPath = $fullFilePath . '.backup.' . date('Y-m-d_H-i-s');
            if (!copy($fullFilePath, $backupPath)) {
                return new ToolResult(false, 'Failed to create backup: ' . $path, ['code' => 'BACKUP_FAILED']);
            }

            // Обрабатываем содержимое
            $newContent = $this->deleteLines($originalContent, $lineNumbers);

            // Проверяем, были ли изменения
            if ($originalContent === $newContent) {
                // Удаляем резервную копию, так как изменений не было
                unlink($backupPath);

                return new ToolResult(true, 'No changes made to file', [
                    'file_path' => $path,
                    'lines_deleted' => 0,
                    'changes_made' => false,
                ]);
            }

            // Записываем новое содержимое в файл
            $result = file_put_contents($fullFilePath, $newContent);

            if ($result === false) {
                // Восстанавливаем файл из резервной копии в случае ошибки
                copy($backupPath, $fullFilePath);
                unlink($backupPath);

                return new ToolResult(false, 'Failed to write content to file: ' . $path, ['code' => 'WRITE_FAILED']);
            }

            // Удаляем резервную копию после успешной записи
            unlink($backupPath);

            $deletedCount = $this->countDeletedLines($originalContent, $newContent);

            return new ToolResult(true, 'Lines deleted successfully', [
                'file_path' => $path,
                'lines_deleted' => $deletedCount,
                'changes_made' => true,
                'bytes_written' => $result,
                'original_length' => strlen($originalContent),
                'new_length' => strlen($newContent),
            ]);

        } catch (\Throwable $e) {
            return new ToolResult(false, 'Failed to delete lines: ' . $e->getMessage(), ['code' => 'DELETE_LINES_ERROR', 'exception' => get_class($e)]);
        }
    }

    private function deleteLines(string $content, array $lineNumbers): string
    {
        $lines = explode("\n", $content);
        $totalLines = count($lines);

        // Сортируем номера строк в убывающем порядке для корректного удаления
        $sortedLineNumbers = array_unique($lineNumbers);
        rsort($sortedLineNumbers);

        $deletedCount = 0;

        foreach ($sortedLineNumbers as $lineNumber) {
            // Проверяем, что номер строки валидный (1-based)
            if ($lineNumber >= 1 && $lineNumber <= $totalLines) {
                // Удаляем строку (индекс в массиве = номер строки - 1)
                array_splice($lines, $lineNumber - 1, 1);
                $deletedCount++;
                $totalLines--; // Уменьшаем общее количество строк
            }
        }

        return implode("\n", $lines);
    }

    private function countDeletedLines(string $originalContent, string $newContent): int
    {
        $originalLines = substr_count($originalContent, "\n") + 1;
        $newLines = substr_count($newContent, "\n") + 1;
        return $originalLines - $newLines;
    }

    public function getProps(): array
    {
        return [
            'type' => 'function',
            'function' => [
                'name' => $this->getName(),
                'description' => "Delete specific lines from a file by their 1-based line numbers",
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'url' => [
                            'type' => 'string',
                            'description' => 'Git repository URL',
                        ],
                        'path' => [
                            'type' => 'string',
                            'description' => 'Path to the target file within the repository',
                        ],
                        'line_numbers' => [
                            'type' => 'array',
                            'items' => [
                                'type' => 'integer'
                            ],
                            'description' => 'Array of 1-based line numbers to delete from the file',
                        ]
                    ],
                    'required' => ['url', 'path', 'line_numbers'],
                ]
            ]
        ];
    }

    public function getName(): string
    {
        return self::NAME;
    }
}
