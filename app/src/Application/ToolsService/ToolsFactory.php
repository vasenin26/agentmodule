<?php

namespace Anymodule\Agentmodule\Application\ToolsService;


use Anymodule\Agentmodule\Application\Tools\CurrentTime;
use Anymodule\Agentmodule\Application\Tools\Editor\EditFile;
use Anymodule\Agentmodule\Application\Tools\Editor\InsertOrReplace;
use Anymodule\Agentmodule\Application\Tools\Editor\ReplaceInFile;
use Anymodule\Agentmodule\Application\Tools\Git\AnalyzeClasses;
use Anymodule\Agentmodule\Application\Tools\Git\AnalyzeStructure;
use Anymodule\Agentmodule\Application\Tools\Git\FindConfigFiles;
use Anymodule\Agentmodule\Application\Tools\Git\GetDependencies;
use Anymodule\Agentmodule\Application\Tools\Git\GrepFile;
use Anymodule\Agentmodule\Application\Tools\Git\ReadDir;
use Anymodule\Agentmodule\Application\Tools\Git\ReadFile;
use Anymodule\Agentmodule\Application\Tools\Git\ReadFileLines;
use Anymodule\Agentmodule\Application\Tools\Git\RepoManagement\AddFile;
use Anymodule\Agentmodule\Application\Tools\Git\RepoManagement\Commit;
use Anymodule\Agentmodule\Application\Tools\Git\RepoManagement\GetCurrentBranch;
use Anymodule\Agentmodule\Application\Tools\Git\RepoManagement\GetStatus;
use Anymodule\Agentmodule\Application\Tools\Git\RepoManagement\Pull;
use Anymodule\Agentmodule\Application\Tools\Git\RepoManagement\Push;
use Anymodule\Agentmodule\Application\Tools\Git\RepoManagement\ResetHard;
use Anymodule\Agentmodule\Application\Tools\Git\RepoManagement\UnstageFile;
use Anymodule\Agentmodule\Application\Tools\Git\SearchFileByName;
use Anymodule\Agentmodule\Application\Tools\Git\SearchPattern;
use Anymodule\Agentmodule\Application\Tools\Page\FindRelatedPages;
use Anymodule\Agentmodule\Application\Tools\Page\GetActualizationInfo;
use Anymodule\Agentmodule\Application\Tools\Page\GetAttachedFiles;
use Anymodule\Agentmodule\Application\Tools\Page\GetHierarchyTree;
use Anymodule\Agentmodule\Application\Tools\Page\GetInfo;
use Anymodule\Agentmodule\Application\Tools\Page\GetProjectPages;
use Anymodule\Agentmodule\Application\Tools\Page\GetTaskHistory;
use Anymodule\Agentmodule\Application\Tools\SendResult;
use Anymodule\Agentmodule\Application\Tools\Tasks\AddTasks;
use Anymodule\Agentmodule\Application\Tools\Tasks\CompleteTask;
use Anymodule\Agentmodule\Application\Tools\Tasks\ListTasks;
use Anymodule\Agentmodule\Application\Tools\Tasks\TasksStorage;
use Anymodule\Agentmodule\Interface\Git\GitRepoProviderInterface;
use Anymodule\Agentmodule\Interface\Page\PageContextServiceFactoryInterface;
use Anymodule\Agentmodule\Interface\Tools\ToolInterface;

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

    // Repo Management утилиты
    public function gitGetCurrentBranch(): ToolInterface
    {
        return new GetCurrentBranch($this->gitRepoProvider);
    }

    public function gitPull(): ToolInterface
    {
        return new Pull($this->gitRepoProvider);
    }

    public function gitResetHard(): ToolInterface
    {
        return new ResetHard($this->gitRepoProvider);
    }

    public function gitAddFile(): ToolInterface
    {
        return new AddFile($this->gitRepoProvider);
    }

    public function gitUnstageFile(): ToolInterface
    {
        return new UnstageFile($this->gitRepoProvider);
    }

    public function gitCommit(): ToolInterface
    {
        return new Commit($this->gitRepoProvider);
    }

    public function gitPush(): ToolInterface
    {
        return new Push($this->gitRepoProvider);
    }

    public function gitGetStatus(): ToolInterface
    {
        return new GetStatus($this->gitRepoProvider);
    }
}
