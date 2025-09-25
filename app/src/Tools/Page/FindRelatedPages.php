<?php

namespace Anymodule\Agentmodule\Tools\Page;

use Anymodule\Agentmodule\Interface\Page\PageContextServiceInterface;
use Anymodule\Agentmodule\Interface\Tools\ToolInterface;

class FindRelatedPages implements ToolInterface
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

            $relatedPages = $this->findDetailedRelatedPages($page);

            return json_encode([
                'success' => true,
                'data' => [
                    'source_page' => [
                        'id' => $page->id,
                        'title' => $page->title
                    ],
                    'project_id' => $this->pageContextService->getProjectId(),
                    'total_related' => count($relatedPages),
                    'related_pages' => $relatedPages
                ],
                'message' => 'Related pages found successfully',
            ]);

        } catch (\Exception $e) {
            return json_encode([
                'success' => false,
                'error' => 'Failed to find related pages: ' . $e->getMessage(),
                'code' => 'FIND_RELATED_ERROR',
            ]);
        }
    }

    private function findDetailedRelatedPages($sourcePage): array
    {
        $allPages = $this->pageContextService->getAllProjectPages();
        $relatedPages = [];

        foreach ($allPages as $page) {
            if ($page->id === $sourcePage->id) {
                continue; // Пропускаем саму страницу
            }

            $relationTypes = $this->detectRelationTypes($sourcePage, $page);
            
            if (!empty($relationTypes)) {
                $relatedPages[] = [
                    'id' => $page->id,
                    'title' => $page->title,
                    'relation_types' => $relationTypes,
                    'relation_strength' => $this->calculateRelationStrength($relationTypes),
                    'files_count' => count($page->files ?? []),
                    'creator' => $this->getCreatorInfo($page),
                    'created_at' => $this->getCreatedAt($page),
                    'common_files' => $this->findCommonFiles($sourcePage, $page),
                    'hierarchical_relation' => $this->getHierarchicalRelation($sourcePage, $page)
                ];
            }
        }

        // Сортируем по силе связи
        usort($relatedPages, function($a, $b) {
            return $b['relation_strength'] <=> $a['relation_strength'];
        });

        return array_slice($relatedPages, 0, 20); // Ограничиваем до 20 результатов
    }

    private function detectRelationTypes($sourcePage, $targetPage): array
    {
        $relationTypes = [];

        // Проверяем иерархические связи
        if ($this->areInSameHierarchy($sourcePage, $targetPage)) {
            $relationTypes[] = 'hierarchical';
        }

        // Проверяем общие файлы
        if ($this->haveCommonFiles($sourcePage, $targetPage)) {
            $relationTypes[] = 'shared_files';
        }

        // Проверяем общего создателя
        if ($this->haveSameCreator($sourcePage, $targetPage)) {
            $relationTypes[] = 'same_creator';
        }

        // Проверяем близость по времени создания (в пределах недели)
        if ($this->haveTemporalProximity($sourcePage, $targetPage)) {
            $relationTypes[] = 'temporal_proximity';
        }

        // Проверяем схожесть в названиях
        if ($this->haveSimilarTitles($sourcePage->title, $targetPage->title)) {
            $relationTypes[] = 'similar_titles';
        }

        // Проверяем упоминания в контенте
        if ($this->hasContentMention($sourcePage, $targetPage)) {
            $relationTypes[] = 'content_reference';
        }

        return $relationTypes;
    }

    private function areInSameHierarchy($sourcePage, $targetPage): bool
    {
        // Проверяем, являются ли страницы родителем/ребенком
        $sourceParentId = $this->getParentId($sourcePage);
        $targetParentId = $this->getParentId($targetPage);
        
        if ($sourceParentId === $targetPage->id || $targetParentId === $sourcePage->id) {
            return true;
        }

        // Проверяем, имеют ли общего родителя
        if ($sourceParentId && $targetParentId && $sourceParentId === $targetParentId) {
            return true;
        }

        return false;
    }

    private function haveCommonFiles($sourcePage, $targetPage): bool
    {
        $sourceFiles = $sourcePage->files ?? [];
        $targetFiles = $targetPage->files ?? [];

        if (empty($sourceFiles) || empty($targetFiles)) {
            return false;
        }

        return !empty(array_intersect($sourceFiles, $targetFiles));
    }

    private function haveSimilarTitles(string $title1, string $title2): bool
    {
        $words1 = $this->extractWords($title1);
        $words2 = $this->extractWords($title2);

        $commonWords = array_intersect($words1, $words2);
        $totalWords = count(array_unique(array_merge($words1, $words2)));

        // Считаем схожими, если общих слов больше 30% от общего количества
        return $totalWords > 0 && (count($commonWords) / $totalWords) > 0.3;
    }

    private function extractWords(string $title): array
    {
        $words = preg_split('/\s+/', strtolower($title));
        
        // Убираем короткие слова (предлоги, союзы)
        return array_filter($words, function($word) {
            return strlen($word) > 2;
        });
    }

    private function hasContentMention($sourcePage, $targetPage): bool
    {
        $sourceContent = strtolower($sourcePage->content ?? '');
        $targetTitle = strtolower($targetPage->title);
        $sourceTitle = strtolower($sourcePage->title);
        $targetContent = strtolower($targetPage->content ?? '');

        // Проверяем упоминание заголовка целевой страницы в контенте исходной
        if (str_contains($sourceContent, $targetTitle)) {
            return true;
        }

        // Проверяем упоминание заголовка исходной страницы в контенте целевой
        if (str_contains($targetContent, $sourceTitle)) {
            return true;
        }

        return false;
    }

    private function calculateRelationStrength(array $relationTypes): float
    {
        $weights = [
            'hierarchical' => 3.0,
            'shared_files' => 2.5,
            'content_reference' => 2.0,
            'similar_titles' => 1.5,
            'same_creator' => 1.0,
            'temporal_proximity' => 0.5
        ];

        $strength = 0;
        foreach ($relationTypes as $type) {
            $strength += $weights[$type] ?? 0;
        }

        return round($strength, 1);
    }

    private function findCommonFiles($sourcePage, $targetPage): array
    {
        $sourceFiles = $sourcePage->files ?? [];
        $targetFiles = $targetPage->files ?? [];

        return array_values(array_intersect($sourceFiles, $targetFiles));
    }

    private function getHierarchicalRelation($sourcePage, $targetPage): ?string
    {
        $sourceParentId = $this->getParentId($sourcePage);
        $targetParentId = $this->getParentId($targetPage);
        
        if ($sourceParentId === $targetPage->id) {
            return 'target_is_parent';
        }

        if ($targetParentId === $sourcePage->id) {
            return 'target_is_child';
        }

        if ($sourceParentId && $targetParentId && $sourceParentId === $targetParentId) {
            return 'siblings';
        }

        return null;
    }

    private function getCreatorInfo($page): array
    {
        // Creator information is not available in current Page entity
        return [
            'id' => null,
            'name' => 'Unknown'
        ];
    }

    private function getCreatedAt($page): string
    {
        // Created at information is not available in current Page entity
        return 'Unknown';
    }

    private function getParentId($page): ?int
    {
        // Parent ID is not available in current Page entity
        return null;
    }

    private function haveSameCreator($sourcePage, $targetPage): bool
    {
        // Creator information is not available in current Page entity
        return false;
    }

    private function haveTemporalProximity($sourcePage, $targetPage): bool
    {
        // Created at information is not available in current Page entity
        return false;
    }

    public function getProps($name): array
    {
        return [
            'type' => 'function',
            'function' => [
                'name' => $name,
                'description' => 'Find pages related to a specific page based on hierarchy, shared files, content references, and other relationships (only works with current page versions in project context)',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'page_id' => [
                            'type' => 'integer',
                            'description' => 'ID of the page to find related pages for',
                        ]
                    ],
                    'required' => ['page_id'],
                ]
            ]
        ];
    }
}
