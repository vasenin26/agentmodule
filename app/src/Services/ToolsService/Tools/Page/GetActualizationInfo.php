<?php

namespace Anymodule\Agentmodule\Services\ToolsService\Tools\Page;

use Anymodule\Agentmodule\Interface\Page\PageContextServiceInterface;
use Anymodule\Agentmodule\Interface\Tools\ToolInterface;

class GetActualizationInfo implements ToolInterface
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
                ]);
            }

            if (!$this->pageContextService->validatePageAccess((int)$pageId)) {
                return json_encode([
                    'success' => false,
                    'error' => 'Page not found or not accessible in current project context',
                    'code' => 'PAGE_ACCESS_DENIED',
                ]);
            }

            $page = $this->pageContextService->getPageWithActualization((int)$pageId);
            if (!$page) {
                return json_encode([
                    'success' => false,
                    'error' => 'Page not found',
                    'code' => 'PAGE_NOT_FOUND',
                ]);
            }

            $actualizationData = $this->buildActualizationData($page);

            return json_encode([
                'success' => true,
                'data' => $actualizationData,
                'message' => 'Actualization information retrieved successfully',
            ]);

        } catch (\Exception $e) {
            return json_encode([
                'success' => false,
                'error' => 'Failed to retrieve actualization info: ' . $e->getMessage(),
                'code' => 'GET_ACTUALIZATION_ERROR',
            ]);
        }
    }

    private function buildActualizationData($page): array
    {
        $data = [
            'page' => [
                'id' => $page->id,
                'title' => $page->title,
            ],
            'project_id' => $this->pageContextService->getProjectId(),
            'has_actualizations' => false, // Недоступно в базовом API
            'total_actualizations' => 0, // Недоступно в базовом API
            'has_active_actualization' => false, // Недоступно в базовом API
            'latest_actualization' => null, // Недоступно в базовом API
            'completed_actualization' => null, // Недоступно в базовом API
            'actualization_history' => [], // Недоступно в базовом API
            'statistics' => [
                'total_count' => 0,
                'completed_count' => 0,
                'failed_count' => 0,
                'pending_count' => 0,
                'processing_count' => 0,
                'success_rate' => 0,
                'average_duration_minutes' => 0,
                'total_tokens_used' => 0
            ]
        ];

        return $data;
    }


    public function getProps($name): array
    {
        return [
            'type' => 'function',
            'function' => [
                'name' => $name,
                'description' => 'Get detailed actualization information for a page including status, history, LLM chat data and statistics (only works with current page versions in project context)',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'page_id' => [
                            'type' => 'integer',
                            'description' => 'ID of the page to get actualization information for',
                        ]
                    ],
                    'required' => ['page_id'],
                ]
            ]
        ];
    }
}
