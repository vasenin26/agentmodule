<?php

namespace Anymodule\Agentmodule\Tools\Git;


use Anymodule\Agentmodule\Interface\Git\GitRepoProviderInterface;
use Anymodule\Agentmodule\Interface\Tools\ToolInterface;

class GetDependencies implements ToolInterface
{
    const NAME = 'git-get-dependencies';

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
                ]);
            }

            $dependencies = [
                'php_dependencies' => $this->getPhpDependencies($repoPath),
                'js_dependencies' => $this->getJsDependencies($repoPath),
                'python_dependencies' => $this->getPythonDependencies($repoPath),
                'conflicts' => []
            ];

            // Простая проверка на конфликты версий
            $dependencies['conflicts'] = $this->detectVersionConflicts($dependencies);

            return json_encode([
                'success' => true,
                'data' => $dependencies,
                'message' => 'Dependencies analyzed successfully',
            ]);

        } catch (\Exception $e) {
            return json_encode([
                'success' => false,
                'error' => 'Failed to analyze dependencies: ' . $e->getMessage(),
                'code' => 'DEPENDENCIES_ERROR',
            ]);
        }
    }

    private function getPhpDependencies(string $repoPath): array
    {
        $composerFile = $repoPath . '/composer.json';
        if (!file_exists($composerFile)) {
            return ['require' => [], 'require-dev' => []];
        }

        $composerContent = @file_get_contents($composerFile);
        if (!$composerContent) {
            return ['require' => [], 'require-dev' => []];
        }

        $composerData = json_decode($composerContent, true);
        if (!$composerData) {
            return ['require' => [], 'require-dev' => []];
        }

        return [
            'require' => $composerData['require'] ?? [],
            'require-dev' => $composerData['require-dev'] ?? []
        ];
    }

    private function getJsDependencies(string $repoPath): array
    {
        $packageFile = $repoPath . '/package.json';
        if (!file_exists($packageFile)) {
            return ['dependencies' => [], 'devDependencies' => []];
        }

        $packageContent = @file_get_contents($packageFile);
        if (!$packageContent) {
            return ['dependencies' => [], 'devDependencies' => []];
        }

        $packageData = json_decode($packageContent, true);
        if (!$packageData) {
            return ['dependencies' => [], 'devDependencies' => []];
        }

        return [
            'dependencies' => $packageData['dependencies'] ?? [],
            'devDependencies' => $packageData['devDependencies'] ?? [],
            'peerDependencies' => $packageData['peerDependencies'] ?? []
        ];
    }

    private function getPythonDependencies(string $repoPath): array
    {
        $dependencies = [];

        // Проверяем requirements.txt
        $requirementsFile = $repoPath . '/requirements.txt';
        if (file_exists($requirementsFile)) {
            $requirementsContent = @file_get_contents($requirementsFile);
            if ($requirementsContent) {
                $lines = explode("\n", $requirementsContent);
                foreach ($lines as $line) {
                    $line = trim($line);
                    if (!empty($line) && !str_starts_with($line, '#')) {
                        $dependencies[] = $line;
                    }
                }
            }
        }

        // Проверяем pyproject.toml
        $pyprojectFile = $repoPath . '/pyproject.toml';
        if (file_exists($pyprojectFile)) {
            // Простая обработка, без полного парсера TOML
            $pyprojectContent = @file_get_contents($pyprojectFile);
            if ($pyprojectContent && str_contains($pyprojectContent, '[tool.poetry.dependencies]')) {
                $dependencies[] = 'Found pyproject.toml with Poetry dependencies';
            }
        }

        return $dependencies;
    }

    private function detectVersionConflicts(array $dependencies): array
    {
        $conflicts = [];

        // Проверяем конфликты PHP версий
        if (isset($dependencies['php_dependencies']['require']['php'])) {
            $phpVersion = $dependencies['php_dependencies']['require']['php'];
            if (str_contains($phpVersion, '^7') && 
                isset($dependencies['php_dependencies']['require']['laravel/framework']) &&
                str_contains($dependencies['php_dependencies']['require']['laravel/framework'], '^9')) {
                $conflicts[] = 'Laravel 9 requires PHP 8.0+, but PHP 7.x is specified';
            }
        }

        // Проверяем конфликты Node.js версий
        if (isset($dependencies['js_dependencies']['dependencies']['vue']) &&
            isset($dependencies['js_dependencies']['dependencies']['react'])) {
            $conflicts[] = 'Both Vue and React are specified as dependencies';
        }

        return $conflicts;
    }

    public function getProps(): array
    {
        return [
            'type' => 'function',
            'function' => [
                'name' => $this->getName(),
                'description' => 'Analyze project dependencies from composer.json, package.json, requirements.txt and other dependency files',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'url' => [
                            'type' => 'string',
                            'description' => 'Git repository URL to analyze dependencies',
                        ]
                    ],
                    'required' => ['url'],
                ]
            ]
        ];
    }

    public function getName(): string
    {
        return self::NAME;
    }
}
