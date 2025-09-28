<?php

namespace Anymodule\Agentmodule\Tools\Page;


use Anymodule\Agentmodule\Interface\Page\PageContextServiceInterface;
use Anymodule\Agentmodule\Interface\Tools\ToolInterface;

class GetHierarchyTree implements ToolInterface
{
    const NAME = 'page-get-hierarchy-tree';

    public function __construct(
        private PageContextServiceInterface $pageContextService
    ) {
    }

    public function execute(array $args): ?string
    {
        try {
            $pageId = isset($args['page_id']) ? (int)$args['page_id'] : null;
            $maxDepth = isset($args['max_depth']) ? (int)$args['max_depth'] : 10;

            // Проверяем доступ к странице, если указана
            if ($pageId && !$this->pageContextService->validatePageAccess($pageId)) {
                return json_encode([
                    'success' => false,
                    'error' => 'Page not found or not accessible in current project context',
                    'code' => 'PAGE_ACCESS_DENIED',
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
            ]);

        } catch (\Exception $e) {
            return json_encode([
                'success' => false,
                'error' => 'Failed to retrieve page hierarchy: ' . $e->getMessage(),
                'code' => 'GET_HIERARCHY_ERROR',
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
                'children_count' => count($page->children),
                'children' => []
            ];

            // Рекурсивно строим дочерние элементы
            if (!empty($page->children)) {
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

    public function getProps(): array
    {
        return [
            'type' => 'function',
            'function' => [
                'name' => $this->getName(),
                'description' => 'Get hierarchical tree structure of pages with detailed information about each page (only works with current page versions in project context)',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'page_id' => [
                            'type' => 'integer',
                            'description' => 'ID of the root page to start hierarchy from (optional, null means all root pages)',
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

    public function getName(): string
    {
        return self::NAME;
    }
}
