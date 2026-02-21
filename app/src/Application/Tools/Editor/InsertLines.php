<?php

namespace Anymodule\Agentmodule\Application\Tools\Editor;

use Anymodule\Agentmodule\Entity\ToolResult;
use Anymodule\Agentmodule\Interface\Git\GitRepoProviderInterface;
use Anymodule\Agentmodule\Interface\Tools\FileModifyingToolInterface;

class InsertLines implements FileModifyingToolInterface
{
    const NAME = 'editor-insert-lines';

    public function __construct(
        private GitRepoProviderInterface $repoProvider
    ) {
    }

    public function execute(array $args): ?ToolResult
    {
        try {
            ['url' => $url, 'path' => $path, 'lines' => $lines, 'line_number' => $lineNumber, 'insert_mode' => $insertMode] = $args + ['insert_mode' => 'after'];

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
            $newContent = $this->insertLines($originalContent, $lines, $lineNumber, $insertMode);

            // Проверяем, были ли изменения
            if ($originalContent === $newContent) {
                // Удаляем резервную копию, так как изменений не было
                unlink($backupPath);
                
                return new ToolResult(true, 'No changes made to file', [
                    'file_path' => $path,
                    'lines_inserted' => 0,
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

            $insertedCount = count($lines);

            return new ToolResult(true, 'Lines inserted successfully', [
                'file_path' => $path,
                'lines_inserted' => $insertedCount,
                'insert_mode' => $insertMode,
                'target_line' => $lineNumber,
                'changes_made' => true,
                'bytes_written' => $result,
                'original_length' => strlen($originalContent),
                'new_length' => strlen($newContent),
            ]);

        } catch (\Throwable $e) {
            return new ToolResult(false, 'Failed to insert lines: ' . $e->getMessage(), ['code' => 'INSERT_LINES_ERROR', 'exception' => get_class($e)]);
        }
    }

    private function insertLines(string $content, array $lines, int $lineNumber, string $insertMode): string
    {
        $fileLines = explode("\n", $content);
        $totalLines = count($fileLines);
        
        // Проверяем, что номер строки валидный (1-based)
        if ($lineNumber < 1) {
            $lineNumber = 1;
        }
        
        if ($lineNumber > $totalLines) {
            $lineNumber = $totalLines + 1;
        }
        
        switch ($insertMode) {
            case 'before':
                // Вставляем перед указанной строкой
                array_splice($fileLines, $lineNumber - 1, 0, $lines);
                break;
                
            case 'after':
                // Вставляем после указанной строки
                array_splice($fileLines, $lineNumber, 0, $lines);
                break;
                
            case 'replace':
                // Заменяем указанную строку первой строкой из массива, остальные вставляем после
                if (count($lines) > 0) {
                    // Заменяем строку на позиции lineNumber - 1 первой строкой
                    $fileLines[$lineNumber - 1] = $lines[0];
                    
                    // Вставляем остальные строки после замененной
                    if (count($lines) > 1) {
                        $remainingLines = array_slice($lines, 1);
                        array_splice($fileLines, $lineNumber, 0, $remainingLines);
                    }
                }
                break;
                
            default:
                // По умолчанию вставляем после
                array_splice($fileLines, $lineNumber, 0, $lines);
                break;
        }
        
        return implode("\n", $fileLines);
    }

    public function getProps(): array
    {
        return [
            'type' => 'function',
            'function' => [
                'name' => $this->getName(),
                'description' => <<<DDD
Insert or replace lines in a file at a specific 1-based line number. 
Choose an insertion mode: 
- "before" to insert before the line;
- "after" to insert after; 
- "replace" replace the specified line with all new lines, inserting them in place of the original line;
DDD,
                'properties' => [
                    'url' => [
                        'type' => 'string',
                        'description' => 'Git repository URL.',
                    ],
                    'path' => [
                        'type' => 'string',
                        'description' => 'Path to the target file within the repository.',
                    ],
                    'lines' => [
                        'type' => 'array',
                        'items' => ['type' => 'string'],
                        'description' => 'Array of lines to insert; each element is a separate line in the file.',
                    ],
                    'line_number' => [
                        'type' => 'integer',
                        'description' => '1-based line number where to insert the lines.',
                    ],
                    'insert_mode' => [
                        'type' => 'string',
                        'enum' => ['before', 'after', 'replace'],
                        'default' => 'after',
                        'description' => <<<MMM
Insertion mode: 
- "before" inserts lines before the specified line; 
- "after" inserts lines after;
- "replace" replace the specified line with all new lines, inserting them in place of the original line;
MMM,
                    ],
                ],
                'required' => ['url', 'path', 'lines', 'line_number'],
            ]
        ];
    }

    public function getName(): string
    {
        return self::NAME;
    }
}
