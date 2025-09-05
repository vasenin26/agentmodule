<?php

namespace Anymodule\Agentmodule\Services\ToolsService\Tools\Git;


use Anymodule\Agentmodule\Interface\Git\GitRepoProviderInterface;
use Anymodule\Agentmodule\Interface\Tools\ToolInterface;

class FindConfigFiles implements ToolInterface
{
    public function __construct(
        private GitRepoProviderInterface $repoProvider
    ) {
    }

    public function execute(array $args): ?string
    {
        try {
            ['url' => $url] = $args;
            $patterns = $args['patterns'] ?? [];

            $repo = $this->repoProvider->getRepo($url);
            $repoPath = $repo->getRepositoryPath();

            if (!is_dir($repoPath)) {
                return json_encode([
                    'success' => false,
                    'error' => 'Repository not found',
                    'code' => 'REPO_NOT_FOUND',
                    'timestamp' => now()->toISOString()
                ]);
            }

            $configFiles = $this->findConfigurationFiles($repoPath, $patterns);

            return json_encode([
                'success' => true,
                'data' => [
                    'config_files' => $configFiles,
                    'total_found' => count($configFiles),
                    'search_patterns' => array_merge($this->getDefaultPatterns(), $patterns)
                ],
                'message' => 'Configuration files found successfully',
                'timestamp' => now()->toISOString()
            ]);

        } catch (\Exception $e) {
            return json_encode([
                'success' => false,
                'error' => 'Failed to find configuration files: ' . $e->getMessage(),
                'code' => 'CONFIG_SEARCH_ERROR',
                'timestamp' => now()->toISOString()
            ]);
        }
    }

    private function findConfigurationFiles(string $repoPath, array $additionalPatterns = []): array
    {
        $configFiles = [];
        $patterns = array_merge($this->getDefaultPatterns(), $additionalPatterns);

        foreach ($patterns as $pattern) {
            $foundFiles = $this->searchByPattern($repoPath, $pattern);
            foreach ($foundFiles as $file) {
                $configFiles[] = [
                    'file' => $file,
                    'type' => $this->detectConfigType($file),
                    'size' => $this->getFileSize($repoPath . '/' . $file),
                    'description' => $this->getConfigDescription($file)
                ];
            }
        }

        // Удаляем дубликаты
        $uniqueFiles = [];
        $seenFiles = [];

        foreach ($configFiles as $config) {
            if (!in_array($config['file'], $seenFiles)) {
                $uniqueFiles[] = $config;
                $seenFiles[] = $config['file'];
            }
        }

        return $uniqueFiles;
    }

    private function getDefaultPatterns(): array
    {
        return [
            '.env*',
            'config/*',
            '*.config.js',
            '*.config.ts',
            'webpack.config.*',
            'vite.config.*',
            'next.config.*',
            'nuxt.config.*',
            'vue.config.*',
            'tailwind.config.*',
            'tsconfig.json',
            'jsconfig.json',
            'babel.config.*',
            'eslint.config.*',
            '.eslintrc*',
            'prettier.config.*',
            '.prettierrc*',
            'phpunit.xml*',
            'phpcs.xml*',
            'docker-compose*.yml',
            'docker-compose*.yaml',
            'Dockerfile*',
            '.dockerignore',
            'Makefile',
            'composer.json',
            'package.json',
            'requirements.txt',
            'pyproject.toml',
            'setup.py',
            'go.mod',
            'pom.xml',
            'build.gradle',
            '.gitignore',
            '.gitattributes',
            'README*'
        ];
    }

    private function searchByPattern(string $repoPath, string $pattern): array
    {
        $files = [];

        try {
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($repoPath, \RecursiveDirectoryIterator::SKIP_DOTS)
            );

            foreach ($iterator as $file) {
                if ($file->isFile()) {
                    $relativePath = str_replace($repoPath . '/', '', $file->getPathname());

                    // Исключаем системные директории
                    if ($this->shouldSkipFile($relativePath)) {
                        continue;
                    }

                    if (fnmatch($pattern, $relativePath) || fnmatch($pattern, basename($relativePath))) {
                        $files[] = $relativePath;
                    }
                }
            }
        } catch (\Exception $e) {
            // Продолжаем поиск, даже если произошла ошибка с конкретным файлом
        }

        return $files;
    }

    private function shouldSkipFile(string $relativePath): bool
    {
        $skipPatterns = [
            'vendor/',
            'node_modules/',
            '.git/',
            'storage/logs/',
            'bootstrap/cache/',
            'var/cache/',
            'var/log/',
            '.next/',
            '.nuxt/',
            'dist/',
            'build/',
            '.idea/',
            '.vscode/'
        ];

        foreach ($skipPatterns as $pattern) {
            if (str_starts_with($relativePath, $pattern)) {
                return true;
            }
        }

        return false;
    }

    private function detectConfigType(string $filename): string
    {
        $basename = basename($filename);
        $extension = pathinfo($filename, PATHINFO_EXTENSION);

        $typeMap = [
            'composer.json' => 'PHP Dependencies',
            'package.json' => 'Node.js Dependencies',
            'requirements.txt' => 'Python Dependencies',
            'go.mod' => 'Go Module',
            'pom.xml' => 'Maven Configuration',
            'build.gradle' => 'Gradle Configuration',
            'webpack.config.js' => 'Webpack Configuration',
            'vite.config.js' => 'Vite Configuration',
            'next.config.js' => 'Next.js Configuration',
            'nuxt.config.js' => 'Nuxt.js Configuration',
            'vue.config.js' => 'Vue.js Configuration',
            'tailwind.config.js' => 'Tailwind CSS Configuration',
            'tsconfig.json' => 'TypeScript Configuration',
            'jsconfig.json' => 'JavaScript Configuration',
            'phpunit.xml' => 'PHPUnit Configuration',
            'docker-compose.yml' => 'Docker Compose',
            'Dockerfile' => 'Docker Configuration',
            'Makefile' => 'Make Configuration',
            '.gitignore' => 'Git Ignore Rules',
            '.env' => 'Environment Variables'
        ];

        if (isset($typeMap[$basename])) {
            return $typeMap[$basename];
        }

        if (str_starts_with($basename, '.env')) {
            return 'Environment Variables';
        }

        if (str_contains($filename, 'config/')) {
            return 'Application Configuration';
        }

        return 'Configuration File';
    }

    private function getFileSize(string $filePath): ?int
    {
        if (file_exists($filePath)) {
            return filesize($filePath);
        }
        return null;
    }

    private function getConfigDescription(string $filename): string
    {
        $basename = basename($filename);

        $descriptions = [
            'composer.json' => 'PHP project dependencies and autoload configuration',
            'package.json' => 'Node.js project metadata and dependencies',
            'requirements.txt' => 'Python project dependencies',
            'webpack.config.js' => 'Webpack build tool configuration',
            'vite.config.js' => 'Vite build tool configuration',
            'next.config.js' => 'Next.js framework configuration',
            'tailwind.config.js' => 'Tailwind CSS utility framework configuration',
            'tsconfig.json' => 'TypeScript compiler configuration',
            'phpunit.xml' => 'PHPUnit testing framework configuration',
            'docker-compose.yml' => 'Multi-container Docker application configuration',
            'Dockerfile' => 'Docker container build instructions',
            '.gitignore' => 'Git version control ignore patterns',
            '.env' => 'Environment-specific configuration variables'
        ];

        return $descriptions[$basename] ?? 'Project file';
    }

    public function getProps($name): array
    {
        return [
            'type' => 'function',
            'function' => [
                'name' => $name,
                'description' => 'Find and analyze configuration files in repository including .env, config directories, webpack, docker files etc.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'url' => [
                            'type' => 'string',
                            'description' => 'Git repository URL to search for configuration files',
                        ],
                        'patterns' => [
                            'type' => 'array',
                            'items' => ['type' => 'string'],
                            'description' => 'Additional file patterns to search for (supports wildcards)',
                        ]
                    ],
                    'required' => ['url'],
                ]
            ]
        ];
    }
}
