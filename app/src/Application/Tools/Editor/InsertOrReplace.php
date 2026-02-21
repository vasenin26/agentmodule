<?php

namespace Anymodule\Agentmodule\Application\Tools\Editor;

use Anymodule\Agentmodule\Entity\ToolResult;
use Anymodule\Agentmodule\Interface\Git\GitRepoProviderInterface;
use Anymodule\Agentmodule\Interface\Tools\FileModifyingToolInterface;

class InsertOrReplace implements FileModifyingToolInterface
{
    const NAME = 'editor-insert-or-replace';

    public function __construct(
        private GitRepoProviderInterface $repoProvider
    ) {
    }

    public function execute(array $args): ?ToolResult
    {
        try {
            ['url' => $url, 'path' => $path, 'content' => $content, 'mode' => $mode, 'create_if_not_exists' => $createIfNotExists] = $args + ['create_if_not_exists' => false];

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

            // Читаем содержимое файла
            $originalContent = file_get_contents($fullFilePath);
            if ($originalContent === false) {
                return new ToolResult(false, 'Failed to read file: ' . $path, ['code' => 'READ_FAILED']);
            }

            // Если файл был создан только что, originalContent будет пустым
            $isNewFile = $createIfNotExists && $originalContent === '';

            // Создаем резервную копию файла только если это не новый файл
            $backupPath = null;
            if (!$isNewFile) {
                $backupPath = $fullFilePath . '.backup.' . date('Y-m-d_H-i-s');
                if (!copy($fullFilePath, $backupPath)) {
                    return json_encode([
                        'success' => false,
                        'error' => 'Failed to create backup of file: ' . $path,
                        'code' => 'BACKUP_FAILED',
                    ]);
                }
            }

            // Обрабатываем содержимое в зависимости от режима
            $newContent = $this->processContent($originalContent, $content, $mode);

            // Проверяем, были ли изменения
            if ($originalContent === $newContent) {
                // Удаляем резервную копию, так как изменений не было
                if ($backupPath) {
                    unlink($backupPath);
                }
                
                return new ToolResult(true, 'No changes made to file', [
                    'file_path' => $path,
                    'mode' => $mode,
                    'changes_made' => false,
                    'file_created' => $isNewFile,
                ]);
            }

            // Записываем новое содержимое в файл
            $result = file_put_contents($fullFilePath, $newContent);

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
                'mode' => $mode,
                'changes_made' => true,
                'file_created' => $isNewFile,
                'bytes_written' => $result,
                'original_length' => strlen($originalContent),
                'new_length' => strlen($newContent),
            ]);

        } catch (\Throwable $e) {
            return new ToolResult(false, 'Failed to insert or replace in file: ' . $e->getMessage(), ['code' => 'INSERT_REPLACE_ERROR', 'exception' => get_class($e)]);
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

    public function getProps(): array
    {
        return [
            'type' => 'function',
            'function' => [
                'name' => $this->getName(),
                'description' => 'Insert or replace content in a file using clear modes. Modes: prepend (add to beginning), append (add to end), replace_all (replace entire file), replace_start (replace first lines), replace_end (replace last lines), insert_at_line (insert at specific 1-based line). Newlines are preserved as-is. For insert_at_line, prefix content with "LINE:<number>\\n".',
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
                        'content' => [
                            'type' => 'string',
                            'description' => 'Content to insert or replace. For insert_at_line mode, prefix with "LINE:<number>\\n" where <number> is the 1-based line. Example: "LINE:5\\n// New comment". For prepend/append, content is added verbatim. For replace_start/replace_end, provide exactly the lines to overwrite at the beginning or end, respectively. For replace_all, the entire file becomes this content.',
                        ],
                        'mode' => [
                            'type' => 'string',
                            'description' => 'Operation mode. prepend: add to beginning; append: add to end; replace_all: replace entire file; replace_start: overwrite first N lines (N is the number of lines in content); replace_end: overwrite last N lines; insert_at_line: insert content before the specified 1-based line using the "LINE:<number>" prefix.',
                            'enum' => ['prepend', 'append', 'replace_all', 'replace_start', 'replace_end', 'insert_at_line']
                        ],
                        'create_if_not_exists' => [
                            'type' => 'boolean',
                            'description' => 'Create the file (and any necessary directories) if it does not exist. When created, empty original content is assumed.',
                            'default' => false
                        ]
                    ],
                    'required' => ['url', 'path', 'content', 'mode'],
                ]
            ]
        ];
    }

    public function getName(): string
    {
        return self::NAME;
    }
}
