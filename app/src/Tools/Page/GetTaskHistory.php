<?php

namespace Anymodule\Agentmodule\Tools\Page;


use Anymodule\Agentmodule\Interface\Page\PageContextServiceInterface;
use Anymodule\Agentmodule\Interface\Tools\ToolInterface;

class GetTaskHistory implements ToolInterface
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

            $page = $this->pageContextService->getPageById((int)$pageId);
            if (!$page) {
                return json_encode([
                    'success' => false,
                    'error' => 'Page not found',
                    'code' => 'PAGE_NOT_FOUND',
                ]);
            }

            $taskHistory = $this->pageContextService->getTaskHistory((int)$pageId);
            $historyData = $this->buildTaskHistoryData($page, $taskHistory);

            return json_encode([
                'success' => true,
                'data' => $historyData,
                'message' => 'Task history retrieved successfully',
            ]);

        } catch (\Exception $e) {
            return json_encode([
                'success' => false,
                'error' => 'Failed to retrieve task history: ' . $e->getMessage(),
                'code' => 'GET_TASK_HISTORY_ERROR',
            ]);
        }
    }

    private function buildTaskHistoryData($page, $taskHistory): array
    {
        $data = [
            'page' => [
                'id' => $page->id,
                'title' => $page->title
            ],
            'project_id' => $this->pageContextService->getProjectId(),
            'total_tasks' => $taskHistory->count(),
            'task_descriptions' => [],
            'statistics' => []
        ];

        // Обрабатываем каждое описание задачи
        $data['task_descriptions'] = $taskHistory->map(function ($diffDescription) {
            return [
                'id' => $diffDescription->id,
                'content' => $diffDescription->content,
                'status' => $diffDescription->generation_status,
                'created_at' => $diffDescription->created_at->toISOString(),
                'updated_at' => $diffDescription->updated_at->toISOString(),
                'creator' => [
                    'id' => $diffDescription->creator->id,
                    'name' => $diffDescription->creator->name
                ],
                'llm_chat' => $diffDescription->llmChat ? [
                    'id' => $diffDescription->llmChat->id,
                    'has_messages' => $diffDescription->llmChat->hasMessages(),
                    'total_tokens' => $diffDescription->llmChat->getTotalTokensOrZero(),
                    'prompt_tokens' => $diffDescription->llmChat->getPromptTokensOrZero(),
                    'completion_tokens' => $diffDescription->llmChat->getCompletionTokensOrZero()
                ] : null,
                'techplane' => $diffDescription->techplane ? [
                    'id' => $diffDescription->techplane->id,
                    'title' => $diffDescription->techplane->title ?? 'Техплан',
                    'status' => $diffDescription->techplane->status ?? 'unknown'
                ] : null,
                'content_length' => strlen($diffDescription->content ?? ''),
                'is_completed' => $diffDescription->isCompleted(),
                'is_generating' => $diffDescription->isGenerating(),
                'has_failed' => $diffDescription->hasFailed()
            ];
        })->toArray();

        // Вычисляем статистику
        $data['statistics'] = $this->calculateTaskStatistics($taskHistory);

        return $data;
    }

    private function calculateTaskStatistics($taskHistory): array
    {
        $statistics = [
            'total_count' => $taskHistory->count(),
            'completed_count' => 0,
            'generating_count' => 0,
            'failed_count' => 0,
            'pending_count' => 0,
            'success_rate' => 0,
            'total_tokens_used' => 0,
            'average_tokens_per_task' => 0,
            'total_content_length' => 0,
            'average_content_length' => 0,
            'unique_creators' => 0,
            'with_techplane_count' => 0,
            'latest_activity' => null,
            'task_frequency_by_month' => []
        ];

        if ($statistics['total_count'] === 0) {
            return $statistics;
        }

        $totalTokens = 0;
        $totalContentLength = 0;
        $creators = [];
        $monthCounts = [];

        foreach ($taskHistory as $task) {
            // Подсчет статусов
            switch ($task->generation_status) {
                case 'completed':
                    $statistics['completed_count']++;
                    break;
                case 'generating':
                    $statistics['generating_count']++;
                    break;
                case 'failed':
                    $statistics['failed_count']++;
                    break;
                case 'pending':
                    $statistics['pending_count']++;
                    break;
            }

            // Подсчет токенов
            if ($task->llmChat) {
                $totalTokens += $task->llmChat->getTotalTokensOrZero();
            }

            // Длина контента
            $contentLength = strlen($task->content ?? '');
            $totalContentLength += $contentLength;

            // Уникальные создатели
            $creators[] = $task->creator->id;

            // Техпланы
            if ($task->techplane) {
                $statistics['with_techplane_count']++;
            }

            // Частота по месяцам
            $monthKey = $task->created_at->format('Y-m');
            $monthCounts[$monthKey] = ($monthCounts[$monthKey] ?? 0) + 1;

            // Последняя активность
            if (!$statistics['latest_activity'] || 
                $task->updated_at > $statistics['latest_activity']) {
                $statistics['latest_activity'] = $task->updated_at->toISOString();
            }
        }

        // Финальные вычисления
        $statistics['success_rate'] = round(
            ($statistics['completed_count'] / $statistics['total_count']) * 100, 1
        );

        $statistics['total_tokens_used'] = $totalTokens;
        $statistics['average_tokens_per_task'] = $statistics['total_count'] > 0 
            ? round($totalTokens / $statistics['total_count'], 1) 
            : 0;

        $statistics['total_content_length'] = $totalContentLength;
        $statistics['average_content_length'] = $statistics['total_count'] > 0 
            ? round($totalContentLength / $statistics['total_count'], 1) 
            : 0;

        $statistics['unique_creators'] = count(array_unique($creators));

        // Сортируем месяцы и ограничиваем последними 12
        ksort($monthCounts);
        $statistics['task_frequency_by_month'] = array_slice($monthCounts, -12, 12, true);

        return $statistics;
    }

    public function getProps($name): array
    {
        return [
            'type' => 'function',
            'function' => [
                'name' => $name,
                'description' => 'Get complete task generation history for a page including diff descriptions, LLM chats, techplanes and statistics (only works with current page versions in project context)',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'page_id' => [
                            'type' => 'integer',
                            'description' => 'ID of the page to get task history for',
                        ]
                    ],
                    'required' => ['page_id'],
                ]
            ]
        ];
    }
}
