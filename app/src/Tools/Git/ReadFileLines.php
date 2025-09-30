<?php

namespace Anymodule\Agentmodule\Tools\Git;

use Anymodule\Agentmodule\Interface\Git\GitRepoProviderInterface;
use Anymodule\Agentmodule\Interface\Tools\ToolInterface;
use Anymodule\Agentmodule\Utils\Log;
use Anymodule\Agentmodule\Entity\ToolResult;

class ReadFileLines implements ToolInterface
{
    const NAME = 'git-read-file-lines';

    public function __construct(
        private GitRepoProviderInterface $repoProvider
    ) {
    }

    public function execute(array $args): ?ToolResult
    {
        try {
            ['url' => $url, 'path' => $path, 'start_line' => $startLine, 'end_line' => $endLine] = $args;

            Log::info('Read lines from path: ' . $path);
            Log::info('Read url: ' . $url);
            Log::info('Lines: ' . $startLine . '-' . $endLine);

            $repo = $this->repoProvider->getRepo($url);
            $fullPath = $repo->getRepositoryPath() . '/' . trim($path, '/');

            if (!file_exists($fullPath)) {
                return new ToolResult(false, 'File not found: ' . $path, ['code' => 'FILE_NOT_FOUND']);
            }

            // Читаем весь файл
            $content = file_get_contents($fullPath);
            if ($content === false) {
                return new ToolResult(false, 'Failed to read file: ' . $path, ['code' => 'READ_FAILED']);
            }

            // Разбиваем на строки
            $lines = explode("\n", $content);
            $totalLines = count($lines);

            // Валидируем и нормализуем номера строк
            if ($startLine < 1) {
                return new ToolResult(false, 'Start line out of range. Must be >= 1', ['code' => 'START_LINE_OUT_OF_RANGE']);
            }

            // Если стартовая строка за пределами файла — сообщаем об ошибке
            if ($startLine > $totalLines) {
                return new ToolResult(false, 'Start line out of range. File has ' . $totalLines . ' lines', ['code' => 'START_LINE_OUT_OF_RANGE']);
            }

            if ($endLine < $startLine) {
                return new ToolResult(false, 'End line out of range. Must be >= start line (' . $startLine . ')', ['code' => 'END_LINE_OUT_OF_RANGE']);
            }

            // Ограничиваем конечную строку длиной файла
            $effectiveEndLine = min($endLine, $totalLines);

            // Извлекаем нужные строки (индексы начинаются с 0)
            $selectedLines = array_slice($lines, $startLine - 1, $effectiveEndLine - $startLine + 1);
            $content = implode("\n", $selectedLines);

            return new ToolResult(true, 'Git: read file lines ok', [
                'file_path' => $path,
                'start_line' => $startLine,
                'end_line' => $effectiveEndLine,
                'total_lines' => $totalLines,
                'lines_count' => count($selectedLines),
                'content' => $content,
            ]);

        } catch (\Throwable $e) {
            return new ToolResult(false, 'Failed to read file lines: ' . $e->getMessage(), ['code' => 'READ_LINES_ERROR', 'exception' => get_class($e)]);
        }
    }

    public function getProps(): array
    {
        return [
            'type' => 'function',
            'function' => [
                'name' => $this->getName(),
                'description' => 'Read specific lines from file in repository',
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
                        'start_line' => [
                            'type' => 'integer',
                            'description' => 'Start line number (1-based)',
                            'minimum' => 1
                        ],
                        'end_line' => [
                            'type' => 'integer',
                            'description' => 'End line number (1-based, inclusive)',
                            'minimum' => 1
                        ]
                    ],
                    'required' => ['url', 'path', 'start_line', 'end_line'],
                ]
            ]
        ];
    }

    public function getName(): string
    {
        return self::NAME;
    }
}
