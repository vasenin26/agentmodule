<?php

namespace Anymodule\Agentmodule\Services\ToolsService;


use Anymodule\Agentmodule\Interface\GitRepoProviderInterface;
use Anymodule\Agentmodule\Interface\PageContextServiceFactoryInterface;
use Anymodule\Agentmodule\Interface\ToolInterface;
use Anymodule\Agentmodule\Services\ToolsService\Tools\CurrentTime;
use Anymodule\Agentmodule\Services\ToolsService\Tools\Git\AnalyzeClasses;
use Anymodule\Agentmodule\Services\ToolsService\Tools\Git\AnalyzeStructure;
use Anymodule\Agentmodule\Services\ToolsService\Tools\Git\FindConfigFiles;
use Anymodule\Agentmodule\Services\ToolsService\Tools\Git\GetDependencies;
use Anymodule\Agentmodule\Services\ToolsService\Tools\Git\ReadDir;
use Anymodule\Agentmodule\Services\ToolsService\Tools\Git\ReadFile;
use Anymodule\Agentmodule\Services\ToolsService\Tools\Git\SearchFileByName;
use Anymodule\Agentmodule\Services\ToolsService\Tools\Git\SearchPattern;
use Anymodule\Agentmodule\Services\ToolsService\Tools\Page\FindRelatedPages;
use Anymodule\Agentmodule\Services\ToolsService\Tools\Page\GetActualizationInfo;
use Anymodule\Agentmodule\Services\ToolsService\Tools\Page\GetAttachedFiles;
use Anymodule\Agentmodule\Services\ToolsService\Tools\Page\GetHierarchyTree;
use Anymodule\Agentmodule\Services\ToolsService\Tools\Page\GetInfo;
use Anymodule\Agentmodule\Services\ToolsService\Tools\Page\GetProjectPages;
use Anymodule\Agentmodule\Services\ToolsService\Tools\Page\GetTaskHistory;
use Anymodule\Agentmodule\Services\ToolsService\Tools\SendResult;

class ToolsFactory
{
    public function __construct(
        private GitRepoProviderInterface $gitRepoProvider,
        private PageContextServiceFactoryInterface $pageContextServiceFactory,
    )
    {

    }

    public function sendResult(): ToolInterface
    {
        return new SendResult();
    }

    public function time(): ToolInterface
    {
        return new CurrentTime();
    }

    public function gitReadFile(): ToolInterface
    {
        return new ReadFile($this->gitRepoProvider);
    }

    public function gitSearchFileByName(): ToolInterface
    {
        return new SearchFileByName($this->gitRepoProvider);
    }

    public function gitReadDir(): ToolInterface
    {
        return new ReadDir($this->gitRepoProvider);
    }

    // Новые Git утилиты
    public function gitAnalyzeStructure(): ToolInterface
    {
        return new AnalyzeStructure($this->gitRepoProvider);
    }

    public function gitGetDependencies(): ToolInterface
    {
        return new GetDependencies($this->gitRepoProvider);
    }

    public function gitSearchPattern(): ToolInterface
    {
        return new SearchPattern($this->gitRepoProvider);
    }

    public function gitFindConfigFiles(): ToolInterface
    {
        return new FindConfigFiles($this->gitRepoProvider);
    }

    public function gitAnalyzeClasses(): ToolInterface
    {
        return new AnalyzeClasses($this->gitRepoProvider);
    }

    // Page утилиты (требуют projectId)
    public function pageGetInfo(int $projectId): ToolInterface
    {
        $pageContextService = $this->pageContextServiceFactory->createForProject($projectId);
        return new GetInfo($pageContextService);
    }

    public function pageGetAttachedFiles(int $projectId): ToolInterface
    {
        $pageContextService = $this->pageContextServiceFactory->createForProject($projectId);
        return new GetAttachedFiles($pageContextService, $this->gitRepoProvider);
    }

    public function pageGetProjectPages(int $projectId): ToolInterface
    {
        $pageContextService = $this->pageContextServiceFactory->createForProject($projectId);
        return new GetProjectPages($pageContextService);
    }

    public function pageGetHierarchyTree(int $projectId): ToolInterface
    {
        $pageContextService = $this->pageContextServiceFactory->createForProject($projectId);
        return new GetHierarchyTree($pageContextService);
    }

    public function pageGetRelatedPages(int $projectId): ToolInterface
    {
        $pageContextService = $this->pageContextServiceFactory->createForProject($projectId);
        return new FindRelatedPages($pageContextService);
    }

    public function pageGetActualizationInfo(int $projectId): ToolInterface
    {
        $pageContextService = $this->pageContextServiceFactory->createForProject($projectId);
        return new GetActualizationInfo($pageContextService);
    }

    public function pageGetTaskHistory(int $projectId): ToolInterface
    {
        $pageContextService = $this->pageContextServiceFactory->createForProject($projectId);
        return new GetTaskHistory($pageContextService);
    }
}
