<?php

namespace Anymodule\Agentmodule\Services\ToolsService\Tools\Editor;

use Anymodule\Agentmodule\Interface\Git\GitRepoProviderInterface;
use Anymodule\Agentmodule\Interface\Tools\ToolInterface;

class InsertOrReplace implements ToolInterface
{
    public function __construct(
        private GitRepoProviderInterface $repoProvider
    ) {
    }

    public function execute(array $args): ?string
    {
        try {
            ['url' => $url, 'path' => $path, 'content' => $content, 'mode' => $mode] = $args;

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
            $originalContent = file_get_contents($fullFilePath);
            if ($originalContent === false) {
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

            // Обрабатываем содержимое в зависимости от режима
            $newContent = $this->processContent($originalContent, $content, $mode);

            // Проверяем, были ли изменения
            if ($originalContent === $newContent) {
                // Удаляем резервную копию, так как изменений не было
                unlink($backupPath);
                
                return json_encode([
                    'success' => true,
                    'message' => 'No changes made to file',
                    'data' => [
                        'file_path' => $path,
                        'mode' => $mode,
                        'changes_made' => false
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

            // Удаляем резервную копию после успешной записи
            unlink($backupPath);

            return json_encode([
                'success' => true,
                'message' => 'File updated successfully',
                'data' => [
                    'file_path' => $path,
                    'mode' => $mode,
                    'changes_made' => true,
                    'bytes_written' => $result,
                    'original_length' => strlen($originalContent),
                    'new_length' => strlen($newContent)
                ]
            ]);

        } catch (\Exception $e) {
            return json_encode([
                'success' => false,
                'error' => 'Failed to insert or replace in file: ' . $e->getMessage(),
                'code' => 'INSERT_REPLACE_ERROR',
            ]);
        }
    }

    private function processContent(string $originalContent, string $content, string $mode): string
    {
        switch ($mode) {
            case 'prepend':
                // Добавляет новый контент в начало файла
                return $content . $originalContent;
                
            case 'append':
                // Добавляет новый контент в конец файла
                return $originalContent . $content;
                
            case 'replace_all':
                // Полностью заменяет содержимое файла новым контентом
                return $content;
                
            case 'replace_start':
                // Заменяет первые строки файла новым контентом
                $lines = explode("\n", $originalContent);
                $contentLines = explode("\n", $content);
                $contentLinesCount = count($contentLines);
                
                // Заменяем первые строки
                for ($i = 0; $i < min($contentLinesCount, count($lines)); $i++) {
                    $lines[$i] = $contentLines[$i];
                }
                
                // Если новый контент длиннее, добавляем оставшиеся строки
                if ($contentLinesCount > count($lines)) {
                    $lines = array_merge($lines, array_slice($contentLines, count($lines)));
                }
                
                return implode("\n", $lines);
                
            case 'replace_end':
                // Заменяет последние строки файла новым контентом
                $lines = explode("\n", $originalContent);
                $contentLines = explode("\n", $content);
                $contentLinesCount = count($contentLines);
                $originalLinesCount = count($lines);
                
                // Заменяем последние строки
                $startIndex = max(0, $originalLinesCount - $contentLinesCount);
                for ($i = 0; $i < $contentLinesCount; $i++) {
                    if ($startIndex + $i < $originalLinesCount) {
                        $lines[$startIndex + $i] = $contentLines[$i];
                    } else {
                        $lines[] = $contentLines[$i];
                    }
                }
                
                return implode("\n", $lines);
                
            case 'insert_at_line':
                // Вставляет новый контент на указанную строку (формат: "LINE:номер_строки\nконтент")
                $lineNumber = $this->extractLineNumber($content);
                $insertContent = $this->extractInsertContent($content);
                
                if ($lineNumber === null) {
                    return $originalContent;
                }
                
                $lines = explode("\n", $originalContent);
                $insertLines = explode("\n", $insertContent);
                
                // Вставляем контент на указанную строку
                array_splice($lines, $lineNumber - 1, 0, $insertLines);
                
                return implode("\n", $lines);
                
            default:
                return $originalContent;
        }
    }

    private function extractLineNumber(string $content): ?int
    {
        // Извлекает номер строки из формата "LINE:номер\nконтент"
        // Возвращает номер строки (1-based) или null если формат неверный
        if (preg_match('/^LINE:(\d+)\n/', $content, $matches)) {
            return (int)$matches[1];
        }
        return null;
    }

    private function extractInsertContent(string $content): string
    {
        // Удаляет префикс "LINE:номер\n" из контента, оставляя только текст для вставки
        return preg_replace('/^LINE:\d+\n/', '', $content);
    }

    public function getProps($name): array
    {
        return [
            'type' => 'function',
            'function' => [
                'name' => $name,
                'description' => 'Insert or replace content in file using various modes. Supports: prepend (add to beginning), append (add to end), replace_all (replace entire file), replace_start (replace first lines), replace_end (replace last lines), insert_at_line (insert at specific line)',
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
                            'description' => 'Content to insert or replace. For insert_at_line mode, prefix with "LINE:number\n" where number is the line position (1-based). Example: "LINE:5\n// New comment"',
                        ],
                        'mode' => [
                            'type' => 'string',
                            'description' => 'Mode of operation: prepend (add content to beginning of file), append (add content to end of file), replace_all (replace entire file content), replace_start (replace first lines of file), replace_end (replace last lines of file), insert_at_line (insert content at specific line number)',
                            'enum' => ['prepend', 'append', 'replace_all', 'replace_start', 'replace_end', 'insert_at_line']
                        ]
                    ],
                    'required' => ['url', 'path', 'content', 'mode'],
                ]
            ]
        ];
    }
}
