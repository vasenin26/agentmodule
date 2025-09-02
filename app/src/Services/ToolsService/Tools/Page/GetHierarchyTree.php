<?php

namespace Anymodule\Agentmodule\Services\ToolsService\Tools\Page;


use Anymodule\Agentmodule\Interface\PageContextServiceInterface;
use Anymodule\Agentmodule\Interface\ToolInterface;

class GetHierarchyTree implements ToolInterface
{
    public function __construct(
        private PageContextServiceInterface $pageContextService
    ) {
    }

    public function execute(array $args): ?string
    {
        try {
            $pageId = isset($args['page_id']) ? (int)$args['page_id'] : null;
            $projectId = isset($args['project_id']) ? (int)$args['project_id'] : null;
            $maxDepth = isset($args['max_depth']) ? (int)$args['max_depth'] : 10;

            // Проверяем контекст проекта
            if ($projectId && $projectId !== $this->pageContextService->getProjectId()) {
                return json_encode([
                    'success' => false,
                    'error' => 'Project ID does not match current context',
                    'code' => 'PROJECT_CONTEXT_MISMATCH',
                    'timestamp' => now()->toISOString()
                ]);
            }

            // Проверяем доступ к странице, если указана
            if ($pageId && !$this->pageContextService->validatePageAccess($pageId)) {
                return json_encode([
                    'success' => false,
                    'error' => 'Page not found or not accessible in current project context',
                    'code' => 'PAGE_ACCESS_DENIED',
                    'timestamp' => now()->toISOString()
                ]);
            }

            $hierarchy = $this->pageContextService->getPageHierarchy($pageId);
            $tree = $this->buildDetailedTree($hierarchy, $maxDepth);

            return json_encode([
                'success' => true,
                'data' => [
                    'project_id' => $this->pageContextService->getProjectId(),
                    'root_page_id' => $pageId,
                    'max_depth' => $maxDepth,
                    'total_pages' => $this->countPagesInTree($tree),
                    'tree' => $tree
                ],
                'message' => 'Page hierarchy retrieved successfully',
                'timestamp' => now()->toISOString()
            ]);

        } catch (\Exception $e) {
            return json_encode([
                'success' => false,
                'error' => 'Failed to retrieve page hierarchy: ' . $e->getMessage(),
                'code' => 'GET_HIERARCHY_ERROR',
                'timestamp' => now()->toISOString()
            ]);
        }
    }

    private function buildDetailedTree($pages, int $maxDepth, int $currentDepth = 0): array
    {
        if ($currentDepth >= $maxDepth) {
            return [];
        }

        $tree = [];

        foreach ($pages as $page) {
            $pageData = [
                'id' => $page->id,
                'title' => $page->title,
                'depth' => $currentDepth,
                'has_content' => !empty(trim($page->content)),
                'content_length' => strlen($page->content ?? ''),
                'files_count' => count($page->files ?? []),
                'has_files' => !empty($page->files),
                'creator' => [
                    'id' => $page->creator->id,
                    'name' => $page->creator->name
                ],
                'created_at' => $page->created_at->toISOString(),
                'updated_at' => $page->updated_at->toISOString(),
                'children_count' => $page->children->count(),
                'children' => []
            ];

            // Рекурсивно строим дочерние элементы
            if ($page->children->isNotEmpty()) {
                $pageData['children'] = $this->buildDetailedTree(
                    $page->children, 
                    $maxDepth, 
                    $currentDepth + 1
                );
            }

            $tree[] = $pageData;
        }

        return $tree;
    }

    private function countPagesInTree(array $tree): int
    {
        $count = count($tree);

        foreach ($tree as $page) {
            if (!empty($page['children'])) {
                $count += $this->countPagesInTree($page['children']);
            }
        }

        return $count;
    }

    public function getProps($name): array
    {
        return [
            'type' => 'function',
            'function' => [
                'name' => $name,
                'description' => 'Get hierarchical tree structure of pages with detailed information about each page (only works with current page versions in project context)',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'page_id' => [
                            'type' => 'integer',
                            'description' => 'ID of the root page to start hierarchy from (optional, null means all root pages)',
                        ],
                        'project_id' => [
                            'type' => 'integer',
                            'description' => 'ID of the project (optional, must match current context if provided)',
                        ],
                        'max_depth' => [
                            'type' => 'integer',
                            'description' => 'Maximum depth of hierarchy to retrieve (default: 10)',
                        ]
                    ],
                    'required' => [],
                ]
            ]
        ];
    }
}
