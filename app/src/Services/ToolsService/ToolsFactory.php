<?php

namespace Anymodule\Agentmodule\Services\ToolsService;


use Anymodule\Agentmodule\Interface\Git\GitRepoProviderInterface;
use Anymodule\Agentmodule\Interface\Page\PageContextServiceFactoryInterface;
use Anymodule\Agentmodule\Interface\Tools\ToolInterface;
use Anymodule\Agentmodule\Tools\CurrentTime;
use Anymodule\Agentmodule\Tools\Editor\EditFile;
use Anymodule\Agentmodule\Tools\Editor\InsertOrReplace;
use Anymodule\Agentmodule\Tools\Editor\ReplaceInFile;
use Anymodule\Agentmodule\Tools\Git\AnalyzeClasses;
use Anymodule\Agentmodule\Tools\Git\AnalyzeStructure;
use Anymodule\Agentmodule\Tools\Git\FindConfigFiles;
use Anymodule\Agentmodule\Tools\Git\GetDependencies;
use Anymodule\Agentmodule\Tools\Git\GrepFile;
use Anymodule\Agentmodule\Tools\Git\ReadDir;
use Anymodule\Agentmodule\Tools\Git\ReadFile;
use Anymodule\Agentmodule\Tools\Git\ReadFileLines;
use Anymodule\Agentmodule\Tools\Git\SearchFileByName;
use Anymodule\Agentmodule\Tools\Git\SearchPattern;
use Anymodule\Agentmodule\Tools\Page\FindRelatedPages;
use Anymodule\Agentmodule\Tools\Page\GetActualizationInfo;
use Anymodule\Agentmodule\Tools\Page\GetAttachedFiles;
use Anymodule\Agentmodule\Tools\Page\GetHierarchyTree;
use Anymodule\Agentmodule\Tools\Page\GetInfo;
use Anymodule\Agentmodule\Tools\Page\GetProjectPages;
use Anymodule\Agentmodule\Tools\Page\GetTaskHistory;
use Anymodule\Agentmodule\Tools\SendResult;
use Anymodule\Agentmodule\Tools\Tasks\AddTasks;
use Anymodule\Agentmodule\Tools\Tasks\CompleteTask;
use Anymodule\Agentmodule\Tools\Tasks\ListTasks;
use Anymodule\Agentmodule\Tools\Tasks\TasksStorage;

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

    public function gitReadFileLines(): ToolInterface
    {
        return new ReadFileLines($this->gitRepoProvider);
    }

    public function gitGrepFile(): ToolInterface
    {
        return new GrepFile($this->gitRepoProvider);
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

    // Editor утилиты
    public function editorEditFile(): ToolInterface
    {
        return new EditFile($this->gitRepoProvider);
    }

    public function editorReplaceInFile(): ToolInterface
    {
        return new ReplaceInFile($this->gitRepoProvider);
    }

    public function editorInsertOrReplace(): ToolInterface
    {
        return new InsertOrReplace($this->gitRepoProvider);
    }

    public function tasksList(TasksStorage $tasksStorage): ToolInterface
    {
        return new ListTasks($tasksStorage);
    }

    public function tasksAdd(TasksStorage $tasksStorage): ToolInterface
    {
        return new AddTasks($tasksStorage);
    }

    public function tasksComplete(TasksStorage $tasksStorage): ToolInterface
    {
        return new CompleteTask($tasksStorage);
    }
}
