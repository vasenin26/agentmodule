<?php

namespace Anymodule\Agentmodule\Application\Tools\Page;


use Anymodule\Agentmodule\Entity\ToolResult;
use Anymodule\Agentmodule\Interface\Page\PageContextServiceInterface;
use Anymodule\Agentmodule\Interface\Tools\ToolInterface;

class GetInfo implements ToolInterface
{
    const NAME = 'page-get-info';

    public function __construct(
        private PageContextServiceInterface $pageContextService
    ) {
    }

    public function execute(array $args): ?ToolResult
    {
        try {
            ['page_id' => $pageId] = $args;

            if (!is_numeric($pageId) || $pageId <= 0) {
                return new ToolResult(false, 'Invalid page ID provided', ['code' => 'INVALID_PAGE_ID']);
            }

            $page = $this->pageContextService->getPageById((int)$pageId);

            if (!$page) {
                return new ToolResult(false, 'Page not found or not accessible in current project context', ['code' => 'PAGE_NOT_FOUND']);
            }

            $pageData = [
                'page' => [
                    'id' => $page->id,
                    'title' => $page->title,
                    'content' => $page->content,
                    'files' => $page->files ?? []
                ]
            ];

            // Дополнительная информация о странице недоступна в базовой сущности Page

            return new ToolResult(true, 'Page information retrieved successfully', $pageData);

        } catch (\Throwable $e) {
            return new ToolResult(false, 'Failed to retrieve page information: ' . $e->getMessage(), ['code' => 'GET_PAGE_INFO_ERROR', 'exception' => get_class($e)]);
        }
    }

    public function getProps(): array
    {
        return [
            'type' => 'function',
            'function' => [
                'name' => $this->getName(),
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

    public function getName(): string
    {
        return self::NAME;
    }
}
