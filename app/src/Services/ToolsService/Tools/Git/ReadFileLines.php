<?php

namespace Anymodule\Agentmodule\Services\ToolsService\Tools\Git;

use Anymodule\Agentmodule\Interface\Git\GitRepoProviderInterface;
use Anymodule\Agentmodule\Interface\Tools\ToolInterface;
use Anymodule\Agentmodule\Utils\Log;

class ReadFileLines implements ToolInterface
{
    public function __construct(
        private GitRepoProviderInterface $repoProvider
    ) {
    }

    public function execute(array $args): ?string
    {
        try {
            ['url' => $url, 'path' => $path, 'start_line' => $startLine, 'end_line' => $endLine] = $args;

            Log::info('Read lines from path: ' . $path);
            Log::info('Read url: ' . $url);
            Log::info('Lines: ' . $startLine . '-' . $endLine);

            $repo = $this->repoProvider->getRepo($url);
            $fullPath = $repo->getRepositoryPath() . '/' . trim($path, '/');

            if (!file_exists($fullPath)) {
                return json_encode([
                    'success' => false,
                    'error' => 'File not found: ' . $path,
                    'code' => 'FILE_NOT_FOUND',
                ]);
            }

            // Читаем весь файл
            $content = file_get_contents($fullPath);
            if ($content === false) {
                return json_encode([
                    'success' => false,
                    'error' => 'Failed to read file: ' . $path,
                    'code' => 'READ_FAILED',
                ]);
            }

            // Разбиваем на строки
            $lines = explode("\n", $content);
            $totalLines = count($lines);

            // Валидируем номера строк
            if ($startLine < 1 || $startLine > $totalLines) {
                return json_encode([
                    'success' => false,
                    'error' => 'Start line out of range. File has ' . $totalLines . ' lines',
                    'code' => 'START_LINE_OUT_OF_RANGE',
                ]);
            }

            if ($endLine < $startLine || $endLine > $totalLines) {
                return json_encode([
                    'success' => false,
                    'error' => 'End line out of range. Must be between ' . $startLine . ' and ' . $totalLines,
                    'code' => 'END_LINE_OUT_OF_RANGE',
                ]);
            }

            // Извлекаем нужные строки (индексы начинаются с 0)
            $selectedLines = array_slice($lines, $startLine - 1, $endLine - $startLine + 1);
            $content = implode("\n", $selectedLines);

            return json_encode([
                'success' => true,
                'data' => [
                    'file_path' => $path,
                    'start_line' => $startLine,
                    'end_line' => $endLine,
                    'total_lines' => $totalLines,
                    'lines_count' => count($selectedLines),
                    'content' => $content
                ],
                'message' => 'File lines read successfully'
            ]);

        } catch (\Exception $e) {
            return json_encode([
                'success' => false,
                'error' => 'Failed to read file lines: ' . $e->getMessage(),
                'code' => 'READ_LINES_ERROR',
            ]);
        }
    }

    public function getProps($name): array
    {
        return [
            'type' => 'function',
            'function' => [
                'name' => $name,
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
}
