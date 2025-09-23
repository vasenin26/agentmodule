<?php

namespace Anymodule\Agentmodule\Services\ToolsService\Tools\Git;


use Anymodule\Agentmodule\Interface\Git\GitRepoProviderInterface;
use Anymodule\Agentmodule\Interface\Tools\ToolInterface;

class SearchPattern implements ToolInterface
{
    public function __construct(
        private GitRepoProviderInterface $repoProvider
    ) {
    }

    public function execute(array $args): ?string
    {
        try {
            [
                'url' => $url, 
                'pattern' => $pattern
            ] = $args;

            $mode = $args['mode'] ?? 'regex'; // 'regex' | 'literal'
            $modifiers = $this->sanitizeModifiers($args['modifiers'] ?? 'iu'); // default case-insensitive, unicode
            $fileExtensions = $args['file_extensions'] ?? ['php', 'js', 'ts', 'vue', 'blade.php'];
            $maxResults = is_int($args['max_results'] ?? null) ? (int) $args['max_results'] : 100;

            $repo = $this->repoProvider->getRepo($url);
            $repoPath = $repo->getRepositoryPath();

            if (!is_dir($repoPath)) {
                return json_encode([
                    'success' => false,
                    'error' => 'Repository not found',
                    'code' => 'REPO_NOT_FOUND',
                ]);
            }

            if (!is_string($pattern) || $pattern === '') {
                return json_encode([
                    'success' => false,
                    'error' => 'Pattern must be a non-empty string',
                    'code' => 'PATTERN_INVALID',
                ]);
            }

            // Build final regex
            $finalPattern = $this->buildFinalPattern($pattern, $mode, $modifiers);

            // Validate regex early
            $isValid = @preg_match($finalPattern, "");
            if ($isValid === false) {
                $regexError = function_exists('preg_last_error_msg') ? preg_last_error_msg() : 'Regex error';
                return json_encode([
                    'success' => false,
                    'error' => 'Invalid regex: ' . $regexError,
                    'code' => 'REGEX_ERROR',
                    'data' => [
                        'pattern_provided' => $pattern,
                        'pattern_final' => $finalPattern,
                        'mode' => $mode,
                        'modifiers' => $modifiers,
                    ]
                ]);
            }

            $results = $this->searchInRepository($repoPath, $finalPattern, $fileExtensions, $maxResults);

            return json_encode([
                'success' => true,
                'data' => [
                    'pattern' => $pattern,
                    'pattern_final' => $finalPattern,
                    'mode' => $mode,
                    'modifiers' => $modifiers,
                    'file_extensions' => $fileExtensions,
                    'matches_count' => count($results),
                    'matches' => $results
                ],
                'message' => 'Pattern search completed successfully',
            ]);

        } catch (\Exception $e) {
            return json_encode([
                'success' => false,
                'error' => 'Failed to search pattern: ' . $e->getMessage(),
                'code' => 'SEARCH_ERROR',
            ]);
        }
    }

    private function searchInRepository(string $repoPath, string $finalPattern, array $fileExtensions, int $maxResults): array
    {
        $results = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($repoPath)
        );

        foreach ($iterator as $file) {
            if ($file->isFile() && $this->shouldSearchInFile($file->getPathname(), $fileExtensions)) {
                $matches = $this->searchInFile($file->getPathname(), $finalPattern, $repoPath);
                if (!empty($matches)) {
                    $results = array_merge($results, $matches);
                }
                
                // Ограничиваем количество результатов для производительности
                if (count($results) >= $maxResults) {
                    break;
                }
            }
        }

        return $results;
    }

    private function shouldSearchInFile(string $filePath, array $fileExtensions): bool
    {
        // Исключаем системные директории
        $excludeDirs = ['vendor', 'node_modules', '.git', 'storage/logs', 'bootstrap/cache'];
        foreach ($excludeDirs as $excludeDir) {
            if (str_contains($filePath, $excludeDir)) {
                return false;
            }
        }

        // Проверяем расширение файла
        foreach ($fileExtensions as $extension) {
            if (str_ends_with($filePath, '.' . $extension)) {
                return true;
            }
        }

        return false;
    }

    private function searchInFile(string $filePath, string $finalPattern, string $repoPath): array
    {
        $content = @file_get_contents($filePath);
        if ($content === false) {
            return [];
        }

        $results = [];
        $lines = explode("\n", $content);
        $relativePath = str_replace($repoPath . '/', '', $filePath);

        foreach ($lines as $lineNumber => $line) {
            if (preg_match($finalPattern, $line, $matches)) {
                $results[] = [
                    'file' => $relativePath,
                    'line' => $lineNumber + 1,
                    'content' => trim($line),
                    'match' => $matches[0] ?? ''
                ];
            }
        }

        return $results;
    }

    private function buildFinalPattern(string $pattern, string $mode, string $modifiers): string
    {
        if ($mode === 'literal') {
            $body = preg_quote($pattern, '~');
            return '~' . $body . '~' . $modifiers;
        }

        // regex mode: if already delimited, trust as-is; else wrap
        $isDelimited = (bool) preg_match('/^([~#\/@%!;`\|]).*\1[imsxuADSUXJ]*$/', $pattern);
        if ($isDelimited) {
            return $pattern;
        }
        return '~' . $pattern . '~' . $modifiers;
    }

    private function sanitizeModifiers(string $modifiers): string
    {
        // allow only valid preg modifiers
        $allowed = ['i','m','s','x','u','A','D','S','U','X','J'];
        $unique = [];
        $chars = str_split($modifiers);
        foreach ($chars as $ch) {
            if (in_array($ch, $allowed, true) && !in_array($ch, $unique, true)) {
                $unique[] = $ch;
            }
        }

        // ensure unicode by default if none provided
        if (empty($unique)) {
            $unique = ['i','u'];
        }
        if (!in_array('u', $unique, true)) {
            $unique[] = 'u';
        }
        return implode('', $unique);
    }

    public function getProps($name): array
    {
        return [
            'type' => 'function',
            'function' => [
                'name' => $name,
                'description' => 'Search for patterns in repository files. Supports regex and literal modes, optional modifiers, and file extension filtering.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'url' => [
                            'type' => 'string',
                            'description' => 'Git repository URL to search in',
                        ],
                        'pattern' => [
                            'type' => 'string',
                            'description' => 'Search pattern. If mode is regex and pattern has no delimiters, it will be wrapped automatically as ~...~ with provided modifiers (default iu). In literal mode, pattern is matched as plain text.',
                        ],
                        'mode' => [
                            'type' => 'string',
                            'description' => 'Search mode: regex (default) or literal (plain text search with escaping).',
                            'enum' => ['regex', 'literal']
                        ],
                        'modifiers' => [
                            'type' => 'string',
                            'description' => 'Regex modifiers to apply when building the pattern (e.g., i, m, s, u). Defaults to iu. Ignored if pattern is already delimited.',
                        ],
                        'file_extensions' => [
                            'type' => 'array',
                            'items' => ['type' => 'string'],
                            'description' => 'File extensions to search in. Default: ["php", "js", "ts", "vue", "blade.php"].',
                        ],
                        'max_results' => [
                            'type' => 'integer',
                            'description' => 'Maximum number of matches to return (default 100).',
                        ],
                    ],
                    'required' => ['url', 'pattern'],
                ]
            ]
        ];
    }
}
