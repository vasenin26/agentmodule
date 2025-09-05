<?php

namespace Anymodule\Agentmodule\Services\PageContext;

use Anymodule\Agentmodule\Entity\Page;
use Anymodule\Agentmodule\Interface\Page\PageApi;
use Anymodule\Agentmodule\Interface\Page\PageContextServiceInterface;
use Illuminate\Database\Eloquent\Collection;

class PageProvider implements PageContextServiceInterface
{
    public function __construct(
        private PageApi $api,
        private int     $projectId
    )
    {
    }

    public function getProjectId(): int
    {
        return $this->projectId;
    }

    public function getPageById(int $pageId): ?Page
    {
        try {
            return $this->api->getPageById($pageId);
        } catch (\Exception $exception) {
            return null;
        }
    }

    public function getAllProjectPages(): array
    {
        try {
            return $this->api->getAllProjectPages($this->projectId);
        } catch (\Exception $exception) {
            return [];
        }
    }

    public function getPageHierarchy(?int $rootPageId = null): array
    {
        try {
            return $this->api->getPageHierarchy($this->projectId, $rootPageId);
        } catch (\Exception $exception) {
            return [];
        }
    }

    public function getPageChildren(int $pageId): array
    {
        try {
            return $this->api->getPageChildren($pageId);
        } catch (\Exception $exception) {
            return [];
        }
    }

    public function getPageParent(int $pageId): ?Page
    {
        try {
            return $this->api->getPageParent($pageId);
        } catch (\Exception $exception) {
            return null;
        }
    }

    public function findRelatedPages(int $pageId): array
    {
        try {
            return $this->api->findRelatedPages($pageId);
        } catch (\Exception $exception) {
            return [];
        }
    }

    public function getPageWithActualization(int $pageId): ?Page
    {
        try {
            return $this->api->getPageWithActualization($pageId);
        } catch (\Exception $exception) {
            return null;
        }
    }

    public function getPageFiles(int $pageId): array
    {
        try {
            return $this->api->getPageFiles($pageId);
        } catch (\Exception $exception) {
            return [];
        }
    }

    public function getTaskHistory(int $pageId): array
    {
        try {
            return $this->api->getTaskHistory($pageId);
        } catch (\Exception $exception) {
            return [];
        }
    }

    public function validatePageAccess(int $pageId): bool
    {
        try {
            return $this->api->validatePageAccess($pageId, $this->projectId);
        } catch (\Exception $exception) {
            return false;
        }
    }

    public function isPageInProject(int $pageId): bool
    {
        try {
            return $this->api->isPageInProject($pageId, $this->projectId);
        } catch (\Exception $exception) {
            return false;
        }
    }
}