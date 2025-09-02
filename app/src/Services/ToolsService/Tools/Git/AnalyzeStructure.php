<?php

namespace Anymodule\Agentmodule\Services\ToolsService\Tools\Git;


use Anymodule\Agentmodule\Interface\GitRepoProviderInterface;
use Anymodule\Agentmodule\Interface\ToolInterface;

class AnalyzeStructure implements ToolInterface
{
    public function __construct(
        private GitRepoProviderInterface $repoProvider
    ) {
    }

    public function execute(array $args): ?string
    {
        try {
            ['url' => $url] = $args;

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

            $analysis = [
                'project_type' => $this->detectProjectType($repoPath),
                'main_directories' => $this->getMainDirectories($repoPath),
                'entry_points' => $this->findEntryPoints($repoPath),
                'config_files' => $this->findConfigFiles($repoPath)
            ];

            return json_encode([
                'success' => true,
                'data' => $analysis,
                'message' => 'Repository structure analyzed successfully',
                'timestamp' => now()->toISOString()
            ]);

        } catch (\Exception $e) {
            return json_encode([
                'success' => false,
                'error' => 'Failed to analyze repository structure: ' . $e->getMessage(),
                'code' => 'ANALYSIS_ERROR',
                'timestamp' => now()->toISOString()
            ]);
        }
    }

    private function detectProjectType(string $repoPath): string
    {
        // Проверяем Laravel
        if (file_exists($repoPath . '/composer.json') && 
            file_exists($repoPath . '/artisan') && 
            is_dir($repoPath . '/app')) {
            return 'Laravel';
        }

        // Проверяем Vue/React проекты
        if (file_exists($repoPath . '/package.json')) {
            $packageJson = @file_get_contents($repoPath . '/package.json');
            if ($packageJson) {
                $packageData = json_decode($packageJson, true);
                if (isset($packageData['dependencies'])) {
                    if (isset($packageData['dependencies']['vue'])) {
                        return 'Vue.js';
                    }
                    if (isset($packageData['dependencies']['react'])) {
                        return 'React';
                    }
                    if (isset($packageData['dependencies']['@nuxt/core'])) {
                        return 'Nuxt.js';
                    }
                    if (isset($packageData['dependencies']['next'])) {
                        return 'Next.js';
                    }
                }
            }
        }

        // Проверяем PHP проекты
        if (file_exists($repoPath . '/composer.json')) {
            return 'PHP';
        }

        // Проверяем Node.js проекты
        if (file_exists($repoPath . '/package.json')) {
            return 'Node.js';
        }

        // Проверяем Python проекты
        if (file_exists($repoPath . '/requirements.txt') || 
            file_exists($repoPath . '/setup.py') ||
            file_exists($repoPath . '/pyproject.toml')) {
            return 'Python';
        }

        // Проверяем Go проекты
        if (file_exists($repoPath . '/go.mod')) {
            return 'Go';
        }

        return 'Unknown';
    }

    private function getMainDirectories(string $repoPath): array
    {
        $mainDirs = [];
        $dirs = @scandir($repoPath);
        
        if ($dirs === false) {
            return [];
        }

        $importantDirs = [
            'app', 'src', 'lib', 'components', 'config', 'database', 'resources', 
            'public', 'storage', 'tests', 'vendor', 'node_modules', 'dist', 'build'
        ];

        foreach ($dirs as $dir) {
            if ($dir === '.' || $dir === '..' || !is_dir($repoPath . '/' . $dir)) {
                continue;
            }

            if (in_array($dir, $importantDirs) || !str_starts_with($dir, '.')) {
                $mainDirs[] = $dir;
            }
        }

        return array_slice($mainDirs, 0, 20); // Ограничиваем до 20 директорий
    }

    private function findEntryPoints(string $repoPath): array
    {
        $entryPoints = [];
        $possibleEntryPoints = [
            'index.php',
            'public/index.php',
            'artisan',
            'index.js',
            'src/index.js',
            'app.js',
            'main.js',
            'server.js',
            'index.html',
            'public/index.html',
            'manage.py',
            'main.py',
            'main.go'
        ];

        foreach ($possibleEntryPoints as $entryPoint) {
            if (file_exists($repoPath . '/' . $entryPoint)) {
                $entryPoints[] = $entryPoint;
            }
        }

        return $entryPoints;
    }

    private function findConfigFiles(string $repoPath): array
    {
        $configFiles = [];
        $possibleConfigFiles = [
            '.env.example',
            '.env',
            'composer.json',
            'package.json',
            'webpack.config.js',
            'vite.config.js',
            'next.config.js',
            'nuxt.config.js',
            'vue.config.js',
            'tailwind.config.js',
            'tsconfig.json',
            'phpunit.xml',
            'docker-compose.yml',
            'Dockerfile',
            'Makefile',
            'requirements.txt',
            'go.mod',
            'pom.xml',
            'build.gradle'
        ];

        foreach ($possibleConfigFiles as $configFile) {
            if (file_exists($repoPath . '/' . $configFile)) {
                $configFiles[] = $configFile;
            }
        }

        return $configFiles;
    }

    public function getProps($name): array
    {
        return [
            'type' => 'function',
            'function' => [
                'name' => $name,
                'description' => 'Analyze repository structure to determine project type, main directories, entry points and configuration files',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'url' => [
                            'type' => 'string',
                            'description' => 'Git repository URL to analyze',
                        ]
                    ],
                    'required' => ['url'],
                ]
            ]
        ];
    }
}
