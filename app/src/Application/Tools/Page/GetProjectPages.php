<?php

namespace Anymodule\Agentmodule\Application\Tools\Page;


use Anymodule\Agentmodule\Entity\ToolResult;
use Anymodule\Agentmodule\Interface\Page\PageContextServiceInterface;
use Anymodule\Agentmodule\Interface\Tools\ToolInterface;

class GetProjectPages implements ToolInterface
{
    const NAME = 'page-get-project-pages';

    public function __construct(
        private PageContextServiceInterface $pageContextService
    ) {
    }

    public function execute(array $args): ?ToolResult
    {
        try {
            // Получаем страницы из контекста проекта (project_id не нужен)
            $pages = $this->pageContextService->getAllProjectPages();

            $pagesData = array_map(function ($page) {
                return [
                    'id' => $page->id,
                    'title' => $page->title,
                ];
            }, $pages);

            // Анализ структуры документации
            $rootPages = []; // PageListDTO не содержит parent_id
            $pageTree = []; // Недоступно в PageListDTO
            $statistics = $this->calculateStatistics($pages);

            return new ToolResult(true, 'Project pages retrieved successfully', [
                'total_pages' => count($pages),
                'root_pages_count' => count($rootPages),
                'pages' => $pagesData,
                'page_tree' => $pageTree,
                'statistics' => $statistics,
            ]);

        } catch (\Throwable $e) {
            return new ToolResult(false, 'Failed to retrieve project pages: ' . $e->getMessage(), ['code' => 'GET_PROJECT_PAGES_ERROR', 'exception' => get_class($e)]);
        }
    }


    private function calculateStatistics($pages): array
    {
        $totalPages = count($pages);

        $statistics = [
            'total_pages' => $totalPages,
            'pages_with_files' => 0, // Недоступно в PageListDTO
            'pages_without_files' => 0, // Недоступно в PageListDTO
            'pages_with_children' => 0, // Недоступно в PageListDTO
            'leaf_pages' => 0, // Недоступно в PageListDTO
            'pages_with_actualizations' => 0, // Недоступно в PageListDTO
            'max_depth' => 0, // Недоступно в PageListDTO
            'avg_files_per_page' => 0, // Недоступно в PageListDTO
            'creators_count' => 0 // Недоступно в PageListDTO
        ];

        return $statistics;
    }


    public function getProps(): array
    {
        return [
            'type' => 'function',
            'function' => [
                'name' => $this->getName(),
                'description' => 'Get all pages for the current project with structure analysis and statistics (only works with current page versions in project context)'
            ]
        ];
    }

    public function getName(): string
    {
        return self::NAME;
    }
}
