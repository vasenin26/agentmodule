<?php

namespace Anymodule\Agentmodule\Tools\Git;

use Anymodule\Agentmodule\Interface\Git\GitRepoProviderInterface;
use Anymodule\Agentmodule\Interface\Tools\ToolInterface;
use Anymodule\Agentmodule\Utils\Log;
use Anymodule\Agentmodule\Entity\ToolResult;

class GrepFile implements ToolInterface
{
    const NAME = 'git-grep-file';

    public function __construct(
        private GitRepoProviderInterface $repoProvider
    ) {
    }

    public function execute(array $args): ?ToolResult
    {
        try {
            ['url' => $url, 'path' => $path, 'pattern' => $pattern] = $args;
            $caseSensitive = $args['case_sensitive'] ?? true;
            $wholeWord = $args['whole_word'] ?? false;
            $regex = $args['regex'] ?? false;

            Log::info('Grep pattern: ' . $pattern);
            Log::info('File path: ' . $path);
            Log::info('Case sensitive: ' . ($caseSensitive ? 'yes' : 'no'));

            $repo = $this->repoProvider->getRepo($url);
            $fullPath = $repo->getRepositoryPath() . '/' . trim($path, '/');

            if (!file_exists($fullPath)) {
                return new ToolResult(false, 'File not found: ' . $path, ['code' => 'FILE_NOT_FOUND']);
            }

            // Читаем содержимое файла
            $content = file_get_contents($fullPath);
            if ($content === false) {
                return new ToolResult(false, 'Failed to read file: ' . $path, ['code' => 'READ_FAILED']);
            }

            // Разбиваем на строки
            $lines = explode("\n", $content);
            $matches = [];
            $totalLines = count($lines);

            // Подготавливаем паттерн для поиска
            $searchPattern = $this->preparePattern($pattern, $caseSensitive, $wholeWord, $regex);

            // Ищем совпадения
            foreach ($lines as $lineNumber => $line) {
                $lineNumber++; // Нумерация строк начинается с 1
                
                if ($this->isMatch($line, $searchPattern, $regex)) {
                    $matches[] = [
                        'line_number' => $lineNumber,
                        'line_content' => $line,
                        'match_position' => $this->getMatchPosition($line, $searchPattern, $regex)
                    ];
                }
            }

            return new ToolResult(true, 'Search: pattern found in ' . count($matches) . ' lines', [
                'file_path' => $path,
                'pattern' => $pattern,
                'total_lines' => $totalLines,
                'matches_count' => count($matches),
                'matches' => $matches,
                'search_options' => [
                    'case_sensitive' => $caseSensitive,
                    'whole_word' => $wholeWord,
                    'regex' => $regex,
                ],
            ]);

        } catch (\Throwable $e) {
            return new ToolResult(false, 'Failed to grep file: ' . $e->getMessage(), ['code' => 'GREP_ERROR', 'exception' => get_class($e)]);
        }
    }

    private function preparePattern(string $pattern, bool $caseSensitive, bool $wholeWord, bool $regex): string
    {
        if ($regex) {
            // Для регулярных выражений просто возвращаем паттерн
            return $pattern;
        }

        // Экранируем специальные символы для обычного поиска
        $escapedPattern = preg_quote($pattern, '/');

        if ($wholeWord) {
            // Добавляем границы слов
            $escapedPattern = '\b' . $escapedPattern . '\b';
        }

        return $escapedPattern;
    }

    private function isMatch(string $line, string $pattern, bool $regex): bool
    {
        if ($regex) {
            return preg_match('/' . $pattern . '/', $line) === 1;
        } else {
            return preg_match('/' . $pattern . '/', $line) === 1;
        }
    }

    private function getMatchPosition(string $line, string $pattern, bool $regex): ?int
    {
        if ($regex) {
            if (preg_match('/' . $pattern . '/', $line, $matches, PREG_OFFSET_CAPTURE)) {
                return $matches[0][1];
            }
        } else {
            if (preg_match('/' . $pattern . '/', $line, $matches, PREG_OFFSET_CAPTURE)) {
                return $matches[0][1];
            }
        }
        return null;
    }

    public function getProps(): array
    {
        return [
            'type' => 'function',
            'function' => [
                'name' => $this->getName(),
                'description' => 'Search for pattern in file and return all matching lines',
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
                            'description' => 'Search pattern (text or regex)',
                        ],
                        'case_sensitive' => [
                            'type' => 'boolean',
                            'description' => 'Case sensitive search (default: true)',
                            'default' => true
                        ],
                        'whole_word' => [
                            'type' => 'boolean',
                            'description' => 'Match whole words only (default: false)',
                            'default' => false
                        ],
                        'regex' => [
                            'type' => 'boolean',
                            'description' => 'Treat pattern as regex (default: false)',
                            'default' => false
                        ]
                    ],
                    'required' => ['url', 'path', 'pattern'],
                ]
            ]
        ];
    }

    public function getName(): string
    {
        return self::NAME;
    }
}
