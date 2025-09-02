<?php

namespace Anymodule\Agentmodule\Interface;

use App\Models\Page;
use Illuminate\Database\Eloquent\Collection;

interface PageContextServiceInterface
{
    /**
     * Получение контекста (неизменяемого после создания)
     */
    public function getProjectId(): int;
    
    /**
     * Основные методы работы со страницами проекта
     */
    public function getPageById(int $pageId): ?Page;
    public function getCurrentPages(): Collection;
    public function getAllProjectPages(): Collection;
    
    /**
     * Иерархия и связи
     */
    public function getPageHierarchy(?int $rootPageId = null): Collection;
    public function getPageChildren(int $pageId): Collection;
    public function getPageParent(int $pageId): ?Page;
    public function findRelatedPages(int $pageId): Collection;
    
    /**
     * Специализированные методы
     */
    public function getPageWithActualization(int $pageId): ?Page;
    public function getPageFiles(int $pageId): array;
    public function getTaskHistory(int $pageId): Collection;
    
    /**
     * Валидация и проверки
     */
    public function validatePageAccess(int $pageId): bool;
    public function isPageInProject(int $pageId): bool;
}
