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
            
            $fileExtensions = $args['file_extensions'] ?? ['php', 'js', 'ts', 'vue', 'blade.php'];

            $repo = $this->repoProvider->getRepo($url);
            $repoPath = $repo->getRepositoryPath();

            if (!is_dir($repoPath)) {
                return json_encode([
                    'success' => false,
                    'error' => 'Repository not found',
                    'code' => 'REPO_NOT_FOUND',
                ]);
            }

            $results = $this->searchInRepository($repoPath, $pattern, $fileExtensions);

            return json_encode([
                'success' => true,
                'data' => [
                    'pattern' => $pattern,
                    'file_extensions' => $fileExtensions,
                    'matches_count' => count($results),
                    'matches' => array_slice($results, 0, 100) // Ограничиваем до 100 результатов
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

    private function searchInRepository(string $repoPath, string $pattern, array $fileExtensions): array
    {
        $results = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($repoPath)
        );

        foreach ($iterator as $file) {
            if ($file->isFile() && $this->shouldSearchInFile($file->getPathname(), $fileExtensions)) {
                $matches = $this->searchInFile($file->getPathname(), $pattern, $repoPath);
                if (!empty($matches)) {
                    $results = array_merge($results, $matches);
                }
                
                // Ограничиваем количество результатов для производительности
                if (count($results) >= 100) {
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

    private function searchInFile(string $filePath, string $pattern, string $repoPath): array
    {
        $content = @file_get_contents($filePath);
        if ($content === false) {
            return [];
        }

        $results = [];
        $lines = explode("\n", $content);
        $relativePath = str_replace($repoPath . '/', '', $filePath);

        foreach ($lines as $lineNumber => $line) {
            if (preg_match('/' . preg_quote($pattern, '/') . '/i', $line, $matches)) {
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

    public function getProps($name): array
    {
        return [
            'type' => 'function',
            'function' => [
                'name' => $name,
                'description' => 'Search for patterns in repository files using regular expressions',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'url' => [
                            'type' => 'string',
                            'description' => 'Git repository URL to search in',
                        ],
                        'pattern' => [
                            'type' => 'string',
                            'description' => 'Search pattern (supports regular expressions)',
                        ],
                        'file_extensions' => [
                            'type' => 'array',
                            'items' => ['type' => 'string'],
                            'description' => 'File extensions to search in (default: php, js, ts, vue, blade.php)',
                        ]
                    ],
                    'required' => ['url', 'pattern'],
                ]
            ]
        ];
    }
}
