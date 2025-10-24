<?php

namespace Anymodule\Agentmodule\Application\Tools\Editor;

use Anymodule\Agentmodule\Entity\ToolResult;
use Anymodule\Agentmodule\Interface\Git\GitRepoProviderInterface;
use Anymodule\Agentmodule\Interface\Tools\ToolInterface;

class ChangeLine implements ToolInterface
{
    const NAME = 'editor-change-line';

    public function __construct(
        private GitRepoProviderInterface $repoProvider
    ) {
    }

    public function execute(array $args): ?ToolResult
    {
        try {
            ['url' => $url, 'path' => $path, 'content' => $content, 'line' => $line] = $args;

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
            $fileContent = file_get_contents($fullFilePath);
            if ($fileContent === false) {
                return new ToolResult(false, 'Failed to read file: ' . $path, ['code' => 'READ_FAILED']);
            }

            // Разбиваем файл на строки
            $lines = explode("\n", $fileContent);
            $totalLines = count($lines);

            if ($line < 1 || $line > $totalLines) {
                return new ToolResult(false, "Line number $line is out of range. File has $totalLines lines", ['code' => 'INVALID_LINE_NUMBER']);
            }

            // Заменяем указанную строку (индекс массива начинается с 0)
            $lines[$line - 1] = $content;
            $newContent = implode("\n", $lines);

            // Записываем новое содержимое в файл
            $result = file_put_contents($fullFilePath, $newContent);

            if ($result === false) {
                return new ToolResult(false, 'Failed to write content to file: ' . $path, ['code' => 'WRITE_FAILED']);
            }

            return new ToolResult(true, 'File updated successfully', [
                'file_path' => $path,
                'line_number' => $line,
                'bytes_written' => $result,
                'content_length' => strlen($newContent),
                'total_lines' => $totalLines,
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
                'description' => 'Replace the content of a specific line in a file with new content (optimal for targeted edits).',
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
                            'description' => 'New content to write into the specified line (replaces existing content of that line).',
                        ],
                        'line' => [
                            'type' => 'number',
                            'description' => 'Target line number to replace (1-based index).',
                        ]
                    ],
                    'required' => ['url', 'path', 'content', 'line'],
                ]
            ]
        ];
    }

    public function getName(): string
    {
        return self::NAME;
    }
}
