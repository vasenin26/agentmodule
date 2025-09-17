<?php

namespace Anymodule\Agentmodule\Interface\Page;

use Anymodule\Agentmodule\Entity\Page;
use Anymodule\Agentmodule\Entity\PageVersion;

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

    /**
     * @return string[] - название + ID
     */
    public function getAllProjectPages(): array;
    
    /**
     * Иерархия и связи
     */
    public function getPageHierarchy(?int $rootPageId = null): array;
    public function getPageChildren(int $pageId): array;
    public function getPageParent(int $pageId): ?Page;
    public function findRelatedPages(int $pageId): array;
    
    /**
     * Специализированные методы
     */
    public function getPageWithActualization(int $pageId): ?Page;
    public function getPageFiles(int $pageId): array;
    public function getTaskHistory(int $pageId): array;
    
    /**
     * Получение данных версии страницы по её идентификатору.
     */
    public function getPageVersion(string $versionId): ?PageVersion;
    
    /**
     * Валидация и проверки
     */
    public function validatePageAccess(int $pageId): bool;
    public function isPageInProject(int $pageId): bool;
}
