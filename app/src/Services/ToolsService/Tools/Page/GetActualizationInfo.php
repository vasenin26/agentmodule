<?php

namespace Anymodule\Agentmodule\Services\ToolsService\Tools\Page;

use Anymodule\Agentmodule\Interface\PageContextServiceInterface;
use Anymodule\Agentmodule\Interface\ToolInterface;

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
                    'timestamp' => now()->toISOString()
                ]);
            }

            if (!$this->pageContextService->validatePageAccess((int)$pageId)) {
                return json_encode([
                    'success' => false,
                    'error' => 'Page not found or not accessible in current project context',
                    'code' => 'PAGE_ACCESS_DENIED',
                    'timestamp' => now()->toISOString()
                ]);
            }

            $page = $this->pageContextService->getPageWithActualization((int)$pageId);
            if (!$page) {
                return json_encode([
                    'success' => false,
                    'error' => 'Page not found',
                    'code' => 'PAGE_NOT_FOUND',
                    'timestamp' => now()->toISOString()
                ]);
            }

            $actualizationData = $this->buildActualizationData($page);

            return json_encode([
                'success' => true,
                'data' => $actualizationData,
                'message' => 'Actualization information retrieved successfully',
                'timestamp' => now()->toISOString()
            ]);

        } catch (\Exception $e) {
            return json_encode([
                'success' => false,
                'error' => 'Failed to retrieve actualization info: ' . $e->getMessage(),
                'code' => 'GET_ACTUALIZATION_ERROR',
                'timestamp' => now()->toISOString()
            ]);
        }
    }

    private function buildActualizationData($page): array
    {
        $data = [
            'page' => [
                'id' => $page->id,
                'title' => $page->title,
                'current' => $page->current
            ],
            'project_id' => $this->pageContextService->getProjectId(),
            'has_actualizations' => $page->actualizations->isNotEmpty(),
            'total_actualizations' => $page->actualizations->count(),
            'has_active_actualization' => $page->hasActiveActualization(),
            'latest_actualization' => null,
            'completed_actualization' => null,
            'actualization_history' => []
        ];

        // Последняя актуализация
        if ($page->latestActualization) {
            $data['latest_actualization'] = [
                'id' => $page->latestActualization->id,
                'status' => $page->latestActualization->status,
                'created_at' => $page->latestActualization->created_at->toISOString(),
                'updated_at' => $page->latestActualization->updated_at->toISOString(),
                'created_by' => [
                    'id' => $page->latestActualization->createdBy->id,
                    'name' => $page->latestActualization->createdBy->name
                ],
                'llm_chat' => $page->latestActualization->llmChat ? [
                    'id' => $page->latestActualization->llmChat->id,
                    'has_messages' => $page->latestActualization->llmChat->hasMessages(),
                    'tokens_calculated' => $page->latestActualization->llmChat->isTokensCalculated(),
                    'total_tokens' => $page->latestActualization->llmChat->getTotalTokensOrZero()
                ] : null
            ];
        }

        // Завершенная актуализация
        if ($page->completedActualization) {
            $data['completed_actualization'] = [
                'id' => $page->completedActualization->id,
                'created_at' => $page->completedActualization->created_at->toISOString(),
                'updated_at' => $page->completedActualization->updated_at->toISOString(),
                'created_by' => [
                    'id' => $page->completedActualization->createdBy->id,
                    'name' => $page->completedActualization->createdBy->name
                ],
                'llm_chat' => $page->completedActualization->llmChat ? [
                    'id' => $page->completedActualization->llmChat->id,
                    'has_messages' => $page->completedActualization->llmChat->hasMessages(),
                    'tokens_calculated' => $page->completedActualization->llmChat->isTokensCalculated(),
                    'total_tokens' => $page->completedActualization->llmChat->getTotalTokensOrZero()
                ] : null
            ];
        }

        // История актуализаций
        $data['actualization_history'] = $page->actualizations->map(function ($actualization) {
            return [
                'id' => $actualization->id,
                'status' => $actualization->status,
                'created_at' => $actualization->created_at->toISOString(),
                'updated_at' => $actualization->updated_at->toISOString(),
                'created_by' => [
                    'id' => $actualization->createdBy->id,
                    'name' => $actualization->createdBy->name
                ],
                'duration' => $this->calculateDuration($actualization),
                'llm_chat_summary' => $actualization->llmChat ? [
                    'id' => $actualization->llmChat->id,
                    'total_tokens' => $actualization->llmChat->getTotalTokensOrZero()
                ] : null
            ];
        })->sortByDesc('created_at')->values()->toArray();

        // Статистика
        $data['statistics'] = $this->calculateActualizationStatistics($page->actualizations);

        return $data;
    }

    private function calculateDuration($actualization): ?array
    {
        if ($actualization->status === 'completed' || $actualization->status === 'failed') {
            $duration = $actualization->created_at->diffInMinutes($actualization->updated_at);
            return [
                'minutes' => $duration,
                'human_readable' => $this->formatDuration($duration)
            ];
        }

        return null;
    }

    private function formatDuration(int $minutes): string
    {
        if ($minutes < 60) {
            return "$minutes минут";
        }

        $hours = intval($minutes / 60);
        $remainingMinutes = $minutes % 60;

        if ($hours < 24) {
            return $remainingMinutes > 0 ? "$hours ч $remainingMinutes мин" : "$hours часов";
        }

        $days = intval($hours / 24);
        $remainingHours = $hours % 24;

        return $remainingHours > 0 ? "$days д $remainingHours ч" : "$days дней";
    }

    private function calculateActualizationStatistics($actualizations): array
    {
        $statistics = [
            'total_count' => $actualizations->count(),
            'completed_count' => 0,
            'failed_count' => 0,
            'pending_count' => 0,
            'processing_count' => 0,
            'success_rate' => 0,
            'average_duration_minutes' => 0,
            'total_tokens_used' => 0
        ];

        if ($statistics['total_count'] === 0) {
            return $statistics;
        }

        $completedDurations = [];
        $totalTokens = 0;

        foreach ($actualizations as $actualization) {
            switch ($actualization->status) {
                case 'completed':
                    $statistics['completed_count']++;
                    $duration = $actualization->created_at->diffInMinutes($actualization->updated_at);
                    $completedDurations[] = $duration;
                    break;
                case 'failed':
                    $statistics['failed_count']++;
                    break;
                case 'pending':
                    $statistics['pending_count']++;
                    break;
                case 'processing':
                    $statistics['processing_count']++;
                    break;
            }

            if ($actualization->llmChat) {
                $totalTokens += $actualization->llmChat->getTotalTokensOrZero();
            }
        }

        $statistics['success_rate'] = round(
            ($statistics['completed_count'] / $statistics['total_count']) * 100, 1
        );

        if (!empty($completedDurations)) {
            $statistics['average_duration_minutes'] = round(
                array_sum($completedDurations) / count($completedDurations), 1
            );
        }

        $statistics['total_tokens_used'] = $totalTokens;

        return $statistics;
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
