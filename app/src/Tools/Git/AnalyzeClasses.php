<?php

namespace Anymodule\Agentmodule\Tools\Git;

use Anymodule\Agentmodule\Interface\Git\GitRepoProviderInterface;
use Anymodule\Agentmodule\Interface\Tools\ToolInterface;
use Anymodule\Agentmodule\Entity\ToolResult;

class AnalyzeClasses implements ToolInterface
{
    const NAME = 'git-analyze-classes';

    public function __construct(
        private GitRepoProviderInterface $repoProvider
    ) {
    }

    public function execute(array $args): ?ToolResult
    {
        try {
            ['url' => $url] = $args;
            $namespace = $args['namespace'] ?? null;

            $repo = $this->repoProvider->getRepo($url);
            $repoPath = $repo->getRepositoryPath();

            if (!is_dir($repoPath)) {
                return new ToolResult(false, 'Repository not found', ['code' => 'REPO_NOT_FOUND']);
            }

            $analysis = $this->analyzeRepository($repoPath, $namespace);

            return new ToolResult(true, 'Class analysis completed successfully', $analysis);

        } catch (\Throwable $e) {
            return new ToolResult(false, 'Failed to analyze classes: ' . $e->getMessage(), ['code' => 'CLASS_ANALYSIS_ERROR', 'exception' => get_class($e)]);
        }
    }

    private function analyzeRepository(string $repoPath, ?string $filterNamespace = null): array
    {
        $phpFiles = $this->findPhpFiles($repoPath);
        $classes = [];
        $interfaces = [];
        $traits = [];
        $namespaces = [];

        foreach ($phpFiles as $file) {
            $analysis = $this->analyzePhpFile($repoPath . '/' . $file);
            
            if ($filterNamespace && !$this->matchesNamespace($analysis, $filterNamespace)) {
                continue;
            }

            if (!empty($analysis['classes'])) {
                $classes = array_merge($classes, $analysis['classes']);
            }
            if (!empty($analysis['interfaces'])) {
                $interfaces = array_merge($interfaces, $analysis['interfaces']);
            }
            if (!empty($analysis['traits'])) {
                $traits = array_merge($traits, $analysis['traits']);
            }
            if (!empty($analysis['namespace'])) {
                $namespaces[] = $analysis['namespace'];
            }
        }

        $uniqueNamespaces = array_unique($namespaces);
        
        return [
            'total_php_files' => count($phpFiles),
            'classes_count' => count($classes),
            'interfaces_count' => count($interfaces),
            'traits_count' => count($traits),
            'namespaces_count' => count($uniqueNamespaces),
            'filter_namespace' => $filterNamespace,
            'classes' => array_slice($classes, 0, 50), // Ограничиваем вывод
            'interfaces' => array_slice($interfaces, 0, 20),
            'traits' => array_slice($traits, 0, 20),
            'namespaces' => $uniqueNamespaces,
            'architectural_patterns' => $this->detectArchitecturalPatterns($classes, $interfaces),
            'inheritance_chains' => $this->buildInheritanceChains($classes),
        ];
    }

    private function findPhpFiles(string $repoPath): array
    {
        $phpFiles = [];
        
        try {
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($repoPath, \RecursiveDirectoryIterator::SKIP_DOTS)
            );

            foreach ($iterator as $file) {
                if ($file->isFile() && $file->getExtension() === 'php') {
                    $relativePath = str_replace($repoPath . '/', '', $file->getPathname());
                    
                    // Исключаем системные директории
                    if (!$this->shouldSkipFile($relativePath)) {
                        $phpFiles[] = $relativePath;
                    }
                }
            }
        } catch (\Exception $e) {
            // Продолжаем анализ
        }

        return $phpFiles;
    }

    private function shouldSkipFile(string $relativePath): bool
    {
        $skipPatterns = [
            'vendor/',
            'node_modules/',
            'storage/',
            'bootstrap/cache/',
            'tests/',
            'Test.php',
            '_ide_helper'
        ];

        foreach ($skipPatterns as $pattern) {
            if (str_contains($relativePath, $pattern)) {
                return true;
            }
        }

        return false;
    }

    private function analyzePhpFile(string $filePath): array
    {
        $content = @file_get_contents($filePath);
        if ($content === false) {
            return [];
        }

        $analysis = [
            'file' => $filePath,
            'namespace' => null,
            'classes' => [],
            'interfaces' => [],
            'traits' => []
        ];

        // Извлекаем namespace
        if (preg_match('/namespace\s+([^;]+);/', $content, $matches)) {
            $analysis['namespace'] = trim($matches[1]);
        }

        // Извлекаем классы
        if (preg_match_all('/class\s+(\w+)(?:\s+extends\s+(\w+))?(?:\s+implements\s+([^{]+))?/i', $content, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $className = $match[1];
                $extends = isset($match[2]) ? trim($match[2]) : null;
                $implements = isset($match[3]) ? array_map('trim', explode(',', $match[3])) : [];

                $analysis['classes'][] = [
                    'name' => $className,
                    'full_name' => $analysis['namespace'] ? $analysis['namespace'] . '\\' . $className : $className,
                    'extends' => $extends,
                    'implements' => $implements,
                    'file' => str_replace(getcwd() . '/', '', $filePath),
                    'methods' => $this->extractMethods($content, $className)
                ];
            }
        }

        // Извлекаем интерфейсы
        if (preg_match_all('/interface\s+(\w+)(?:\s+extends\s+([^{]+))?/i', $content, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $interfaceName = $match[1];
                $extends = isset($match[2]) ? array_map('trim', explode(',', $match[2])) : [];

                $analysis['interfaces'][] = [
                    'name' => $interfaceName,
                    'full_name' => $analysis['namespace'] ? $analysis['namespace'] . '\\' . $interfaceName : $interfaceName,
                    'extends' => $extends,
                    'file' => str_replace(getcwd() . '/', '', $filePath),
                    'methods' => $this->extractInterfaceMethods($content, $interfaceName)
                ];
            }
        }

        // Извлекаем трейты
        if (preg_match_all('/trait\s+(\w+)/i', $content, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $traitName = $match[1];

                $analysis['traits'][] = [
                    'name' => $traitName,
                    'full_name' => $analysis['namespace'] ? $analysis['namespace'] . '\\' . $traitName : $traitName,
                    'file' => str_replace(getcwd() . '/', '', $filePath),
                    'methods' => $this->extractMethods($content, $traitName)
                ];
            }
        }

        return $analysis;
    }

    private function extractMethods(string $content, string $className): array
    {
        $methods = [];
        
        // Простое извлечение методов (не идеально, но достаточно для анализа)
        if (preg_match_all('/(?:public|private|protected)\s+(?:static\s+)?function\s+(\w+)\s*\([^)]*\)/i', $content, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $methods[] = $match[1];
            }
        }

        return array_slice($methods, 0, 10); // Ограничиваем количество методов
    }

    private function extractInterfaceMethods(string $content, string $interfaceName): array
    {
        $methods = [];
        
        if (preg_match_all('/(?:public\s+)?function\s+(\w+)\s*\([^)]*\);/i', $content, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $methods[] = $match[1];
            }
        }

        return array_slice($methods, 0, 10);
    }

    private function matchesNamespace(array $analysis, string $filterNamespace): bool
    {
        if (!$analysis['namespace']) {
            return false;
        }

        return str_starts_with($analysis['namespace'], $filterNamespace);
    }

    private function detectArchitecturalPatterns(array $classes, array $interfaces): array
    {
        $patterns = [];

        // Поиск паттерна Repository
        $repositoryClasses = array_filter($classes, function($class) {
            return str_ends_with($class['name'], 'Repository');
        });
        if (!empty($repositoryClasses)) {
            $patterns[] = 'Repository Pattern';
        }

        // Поиск паттерна Service
        $serviceClasses = array_filter($classes, function($class) {
            return str_ends_with($class['name'], 'Service');
        });
        if (!empty($serviceClasses)) {
            $patterns[] = 'Service Layer Pattern';
        }

        // Поиск паттерна Factory
        $factoryClasses = array_filter($classes, function($class) {
            return str_ends_with($class['name'], 'Factory');
        });
        if (!empty($factoryClasses)) {
            $patterns[] = 'Factory Pattern';
        }

        // Поиск паттерна Observer
        $observerClasses = array_filter($classes, function($class) {
            return str_ends_with($class['name'], 'Observer');
        });
        if (!empty($observerClasses)) {
            $patterns[] = 'Observer Pattern';
        }

        // Поиск интерфейсов (Dependency Injection)
        if (!empty($interfaces)) {
            $patterns[] = 'Dependency Injection';
        }

        return $patterns;
    }

    private function buildInheritanceChains(array $classes): array
    {
        $chains = [];

        foreach ($classes as $class) {
            if ($class['extends']) {
                $chains[] = [
                    'child' => $class['name'],
                    'parent' => $class['extends']
                ];
            }
        }

        return array_slice($chains, 0, 20); // Ограничиваем количество цепочек
    }

    public function getProps(): array
    {
        return [
            'type' => 'function',
            'function' => [
                'name' => $this->getName(),
                'description' => 'Analyze PHP classes, interfaces, traits and their relationships to understand code architecture and design patterns',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'url' => [
                            'type' => 'string',
                            'description' => 'Git repository URL to analyze',
                        ],
                        'namespace' => [
                            'type' => 'string',
                            'description' => 'Optional namespace filter to analyze specific namespace only',
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
