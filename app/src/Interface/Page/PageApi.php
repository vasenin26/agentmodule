<?php

namespace Anymodule\Agentmodule\Interface\Page;

use Anymodule\Agentmodule\Entity\Page;
use Anymodule\Agentmodule\Entity\PageVersion;

interface PageApi
{
    /**
     * Получение страницы по ID
     */
    public function getPageById(int $pageId): Page;

    /**
     * Получение всех страниц проекта
     * @return array - массив с информацией о страницах (id, title)
     */
    public function getAllProjectPages(int $projectId): array;

    /**
     * Получение иерархии страниц
     * @param int|null $rootPageId - ID корневой страницы (null для всего проекта)
     * @return array - иерархическая структура страниц
     */
    public function getPageHierarchy(int $projectId, ?int $rootPageId = null): array;

    /**
     * Получение дочерних страниц
     * @return array - массив дочерних страниц
     */
    public function getPageChildren(int $pageId): array;

    /**
     * Получение родительской страницы
     */
    public function getPageParent(int $pageId): ?Page;

    /**
     * Поиск связанных страниц
     * @return array - массив связанных страниц
     */
    public function findRelatedPages(int $pageId): array;

    /**
     * Получение страницы с актуализацией
     */
    public function getPageWithActualization(int $pageId): ?Page;

    /**
     * Получение файлов страницы
     * @return array - массив файлов страницы
     */
    public function getPageFiles(int $pageId): array;

    /**
     * Получение истории задач страницы
     * @return array - массив истории задач
     */
    public function getTaskHistory(int $pageId): array;

    /**
     * Валидация доступа к странице
     */
    public function validatePageAccess(int $pageId, int $projectId): bool;

    /**
     * Проверка принадлежности страницы проекту
     */
    public function isPageInProject(int $pageId, int $projectId): bool;

    /**
     * Получение версии страницы по идентификатору версии в рамках проекта
     */
    public function getPageVersion(int $projectId, string $versionId): PageVersion;
}