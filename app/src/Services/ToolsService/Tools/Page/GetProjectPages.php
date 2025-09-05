<?php

namespace Anymodule\Agentmodule\Services\ToolsService\Tools\Page;


use Anymodule\Agentmodule\Interface\Page\PageContextServiceInterface;
use Anymodule\Agentmodule\Interface\Tools\ToolInterface;

class GetProjectPages implements ToolInterface
{
    public function __construct(
        private PageContextServiceInterface $pageContextService
    ) {
    }

    public function execute(array $args): ?string
    {
        try {
            ['project_id' => $projectId] = $args;

            if (!is_numeric($projectId) || $projectId <= 0) {
                return json_encode([
                    'success' => false,
                    'error' => 'Invalid project ID provided',
                    'code' => 'INVALID_PROJECT_ID',
                    'timestamp' => now()->toISOString()
                ]);
            }

            // Проверяем, что запрашиваемый проект соответствует контексту
            if ((int)$projectId !== $this->pageContextService->getProjectId()) {
                return json_encode([
                    'success' => false,
                    'error' => 'Project ID does not match current context',
                    'code' => 'PROJECT_CONTEXT_MISMATCH',
                    'timestamp' => now()->toISOString()
                ]);
            }

            $pages = $this->pageContextService->getAllProjectPages();

            $pagesData = $pages->map(function ($page) {
                return [
                    'id' => $page->id,
                    'title' => $page->title,
                    'parent_id' => $page->parent_id,
                    'has_children' => $page->children->isNotEmpty(),
                    'children_count' => $page->children->count(),
                    'files_count' => count($page->files ?? []),
                    'has_files' => !empty($page->files),
                    'creator' => [
                        'id' => $page->creator->id,
                        'name' => $page->creator->name
                    ],
                    'created_at' => $page->created_at->toISOString(),
                    'updated_at' => $page->updated_at->toISOString(),
                    'has_actualizations' => $page->actualizations->isNotEmpty(),
                    'latest_actualization' => $page->latestActualization ? [
                        'id' => $page->latestActualization->id,
                        'status' => $page->latestActualization->status,
                        'created_at' => $page->latestActualization->created_at->toISOString()
                    ] : null
                ];
            })->toArray();

            // Анализ структуры документации
            $rootPages = $pages->whereNull('parent_id');
            $pageTree = $this->buildPageTree($pages);
            $statistics = $this->calculateStatistics($pages);

            return json_encode([
                'success' => true,
                'data' => [
                    'project_id' => (int)$projectId,
                    'total_pages' => $pages->count(),
                    'root_pages_count' => $rootPages->count(),
                    'pages' => $pagesData,
                    'page_tree' => $pageTree,
                    'statistics' => $statistics
                ],
                'message' => 'Project pages retrieved successfully',
                'timestamp' => now()->toISOString()
            ]);

        } catch (\Exception $e) {
            return json_encode([
                'success' => false,
                'error' => 'Failed to retrieve project pages: ' . $e->getMessage(),
                'code' => 'GET_PROJECT_PAGES_ERROR',
                'timestamp' => now()->toISOString()
            ]);
        }
    }

    private function buildPageTree($pages, $parentId = null, $level = 0): array
    {
        if ($level > 5) { // Ограничиваем глубину для избежания бесконечной рекурсии
            return [];
        }

        $tree = [];
        $children = $pages->where('parent_id', $parentId);

        foreach ($children as $page) {
            $treeNode = [
                'id' => $page->id,
                'title' => $page->title,
                'level' => $level,
                'children' => $this->buildPageTree($pages, $page->id, $level + 1)
            ];
            $tree[] = $treeNode;
        }

        return $tree;
    }

    private function calculateStatistics($pages): array
    {
        $statistics = [
            'total_pages' => $pages->count(),
            'pages_with_files' => $pages->filter(function ($page) {
                return !empty($page->files);
            })->count(),
            'pages_without_files' => 0,
            'pages_with_children' => $pages->filter(function ($page) {
                return $page->children->isNotEmpty();
            })->count(),
            'leaf_pages' => $pages->filter(function ($page) {
                return $page->children->isEmpty();
            })->count(),
            'pages_with_actualizations' => $pages->filter(function ($page) {
                return $page->actualizations->isNotEmpty();
            })->count(),
            'max_depth' => 0,
            'avg_files_per_page' => 0,
            'creators_count' => $pages->pluck('creator.id')->unique()->count()
        ];

        $statistics['pages_without_files'] = $statistics['total_pages'] - $statistics['pages_with_files'];

        // Вычисляем среднее количество файлов на страницу
        $totalFiles = $pages->sum(function ($page) {
            return count($page->files ?? []);
        });
        $statistics['avg_files_per_page'] = $statistics['total_pages'] > 0 
            ? round($totalFiles / $statistics['total_pages'], 2) 
            : 0;

        // Вычисляем максимальную глубину
        $statistics['max_depth'] = $this->calculateMaxDepth($pages);

        return $statistics;
    }

    private function calculateMaxDepth($pages, $parentId = null, $currentDepth = 0): int
    {
        $children = $pages->where('parent_id', $parentId);
        
        if ($children->isEmpty()) {
            return $currentDepth;
        }

        $maxDepth = $currentDepth;
        foreach ($children as $child) {
            $depth = $this->calculateMaxDepth($pages, $child->id, $currentDepth + 1);
            $maxDepth = max($maxDepth, $depth);
        }

        return $maxDepth;
    }

    public function getProps($name): array
    {
        return [
            'type' => 'function',
            'function' => [
                'name' => $name,
                'description' => 'Get all pages for a specific project with structure analysis and statistics (only works with current page versions)',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'project_id' => [
                            'type' => 'integer',
                            'description' => 'ID of the project to get pages for (must match current context)',
                        ]
                    ],
                    'required' => ['project_id'],
                ]
            ]
        ];
    }
}
