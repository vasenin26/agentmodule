<?php

namespace Anymodule\Agentmodule\Services\ToolsService\Tools\Page;


use Anymodule\Agentmodule\Interface\PageContextServiceInterface;
use Anymodule\Agentmodule\Interface\ToolInterface;

class GetInfo implements ToolInterface
{
    public function __construct(
        private PageContextServiceInterface $pageContextService
    ) {
    }

    public function execute(array $args): ?string
    {
        try {
            ['page_id' => $pageId] = $args;

            if (!is_numeric($pageId) || $pageId <= 0) {
                return json_encode([
                    'success' => false,
                    'error' => 'Invalid page ID provided',
                    'code' => 'INVALID_PAGE_ID',
                    'timestamp' => now()->toISOString()
                ]);
            }

            $page = $this->pageContextService->getPageById((int)$pageId);

            if (!$page) {
                return json_encode([
                    'success' => false,
                    'error' => 'Page not found or not accessible in current project context',
                    'code' => 'PAGE_NOT_FOUND',
                    'timestamp' => now()->toISOString()
                ]);
            }

            $pageData = [
                'page' => [
                    'id' => $page->id,
                    'title' => $page->title,
                    'content' => $page->content,
                    'status' => $page->current ? 'current' : 'draft',
                    'files' => $page->files ?? [],
                    'children_count' => $page->children->count(),
                    'parent_id' => $page->parent_id,
                    'project_id' => $page->project_id,
                    'is_current' => $page->current,
                    'created_at' => $page->created_at->toISOString(),
                    'updated_at' => $page->updated_at->toISOString()
                ],
                'creator' => [
                    'id' => $page->creator->id,
                    'name' => $page->creator->name
                ],
                'project' => [
                    'id' => $page->project->id,
                    'title' => $page->project->title
                ]
            ];

            // Добавляем информацию о родительской странице, если есть
            if ($page->parent) {
                $pageData['parent'] = [
                    'id' => $page->parent->id,
                    'title' => $page->parent->title
                ];
            }

            // Добавляем информацию о дочерних страницах
            if ($page->children->isNotEmpty()) {
                $pageData['children'] = $page->children->map(function ($child) {
                    return [
                        'id' => $child->id,
                        'title' => $child->title,
                        'created_at' => $child->created_at->toISOString()
                    ];
                })->toArray();
            }

            return json_encode([
                'success' => true,
                'data' => $pageData,
                'message' => 'Page information retrieved successfully',
                'timestamp' => now()->toISOString()
            ]);

        } catch (\Exception $e) {
            return json_encode([
                'success' => false,
                'error' => 'Failed to retrieve page information: ' . $e->getMessage(),
                'code' => 'GET_PAGE_INFO_ERROR',
                'timestamp' => now()->toISOString()
            ]);
        }
    }

    public function getProps($name): array
    {
        return [
            'type' => 'function',
            'function' => [
                'name' => $name,
                'description' => 'Get detailed information about a specific page including metadata, content, relationships and creator info (only works with current page versions in project context)',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'page_id' => [
                            'type' => 'integer',
                            'description' => 'ID of the page to retrieve information for',
                        ]
                    ],
                    'required' => ['page_id'],
                ]
            ]
        ];
    }
}
