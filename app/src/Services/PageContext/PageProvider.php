<?php

namespace Anymodule\Agentmodule\Services\PageContext;

use Anymodule\Agentmodule\Interface\PageApi;
use Anymodule\Agentmodule\Interface\PageContextServiceInterface;
use Illuminate\Database\Eloquent\Collection;

class PageProvider implements PageContextServiceInterface
{
    public function __construct(
        PageApi $api,
    )
    {
    }

    public function getProjectId(): int
    {
        // TODO: Implement getProjectId() method.
    }

    public function getPageById(int $pageId): ?Page
    {
        // TODO: Implement getPageById() method.
    }

    public function getCurrentPages(): Collection
    {
        // TODO: Implement getCurrentPages() method.
    }

    public function getAllProjectPages(): Collection
    {
        // TODO: Implement getAllProjectPages() method.
    }

    public function getPageHierarchy(?int $rootPageId = null): Collection
    {
        // TODO: Implement getPageHierarchy() method.
    }

    public function getPageChildren(int $pageId): Collection
    {
        // TODO: Implement getPageChildren() method.
    }

    public function getPageParent(int $pageId): ?Page
    {
        // TODO: Implement getPageParent() method.
    }

    public function findRelatedPages(int $pageId): Collection
    {
        // TODO: Implement findRelatedPages() method.
    }

    public function getPageWithActualization(int $pageId): ?Page
    {
        // TODO: Implement getPageWithActualization() method.
    }

    public function getPageFiles(int $pageId): array
    {
        // TODO: Implement getPageFiles() method.
    }

    public function getTaskHistory(int $pageId): Collection
    {
        // TODO: Implement getTaskHistory() method.
    }

    public function validatePageAccess(int $pageId): bool
    {
        // TODO: Implement validatePageAccess() method.
    }

    public function isPageInProject(int $pageId): bool
    {
        // TODO: Implement isPageInProject() method.
    }
}