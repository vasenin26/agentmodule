<?php

namespace Anymodule\Agentmodule\Application\ToolsService;


use Anymodule\Agentmodule\Application\Tools\CurrentTime;
use Anymodule\Agentmodule\Application\Tools\Editor\ChangeLine;
use Anymodule\Agentmodule\Application\Tools\Editor\DeleteLines;
use Anymodule\Agentmodule\Application\Tools\Editor\EditFile;
use Anymodule\Agentmodule\Application\Tools\Editor\InsertLines;
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
use Anymodule\Agentmodule\Application\Tools\Tasks\TaskStorageInterface;
use Anymodule\Agentmodule\Interface\Git\GitRepoProviderInterface;
use Anymodule\Agentmodule\Interface\Page\PageContextServiceFactoryInterface;
use Anymodule\Agentmodule\Interface\Tools\ToolInterface;
use Anymodule\Agentmodule\Services\TaskStorage\TasksStorage;

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

    public function gitReadFile(GitRepoProviderInterface $repositoryProvider): ToolInterface
    {
        return new ReadFile($repositoryProvider);
    }

    public function gitReadFileLines(GitRepoProviderInterface $repositoryProvider): ToolInterface
    {
        return new ReadFileLines($repositoryProvider);
    }

    public function gitGrepFile(GitRepoProviderInterface $repositoryProvider): ToolInterface
    {
        return new GrepFile($repositoryProvider);
    }

    public function gitSearchFileByName(GitRepoProviderInterface $repositoryProvider): ToolInterface
    {
        return new SearchFileByName($repositoryProvider);
    }

    public function gitReadDir(GitRepoProviderInterface $repositoryProvider): ToolInterface
    {
        return new ReadDir($repositoryProvider);
    }

    // Новые Git утилиты
    public function gitAnalyzeStructure(GitRepoProviderInterface $repositoryProvider): ToolInterface
    {
        return new AnalyzeStructure($repositoryProvider);
    }

    public function gitGetDependencies(GitRepoProviderInterface $repositoryProvider): ToolInterface
    {
        return new GetDependencies($repositoryProvider);
    }

    public function gitSearchPattern(GitRepoProviderInterface $repositoryProvider): ToolInterface
    {
        return new SearchPattern($repositoryProvider);
    }

    public function gitFindConfigFiles(GitRepoProviderInterface $repositoryProvider): ToolInterface
    {
        return new FindConfigFiles($repositoryProvider);
    }

    public function gitAnalyzeClasses(GitRepoProviderInterface $repositoryProvider): ToolInterface
    {
        return new AnalyzeClasses($repositoryProvider);
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
    public function editorEditFile(GitRepoProviderInterface $repositoryProvider): ToolInterface
    {
        return new EditFile($repositoryProvider);
    }

    public function editorReplaceInFile(GitRepoProviderInterface $repositoryProvider): ToolInterface
    {
        return new ReplaceInFile($repositoryProvider);
    }

    public function editorInsertOrReplace(GitRepoProviderInterface $repositoryProvider): ToolInterface
    {
        return new InsertOrReplace($repositoryProvider);
    }

    public function editorChangeLine(GitRepoProviderInterface $repositoryProvider): ToolInterface
    {
        return new ChangeLine($repositoryProvider);
    }

    public function editorDeleteLines(GitRepoProviderInterface $repositoryProvider): ToolInterface
    {
        return new DeleteLines($repositoryProvider);
    }

    public function editorInsertLines(GitRepoProviderInterface $repositoryProvider): ToolInterface
    {
        return new InsertLines($repositoryProvider);
    }

    public function tasksList(TaskStorageInterface $tasksStorage): ToolInterface
    {
        return new ListTasks($tasksStorage);
    }

    public function tasksAdd(TaskStorageInterface $tasksStorage): ToolInterface
    {
        return new AddTasks($tasksStorage);
    }

    public function tasksComplete(TaskStorageInterface $tasksStorage): ToolInterface
    {
        return new CompleteTask($tasksStorage);
    }

    // Repo Management утилиты
    public function gitGetCurrentBranch(GitRepoProviderInterface $repositoryProvider): ToolInterface
    {
        return new GetCurrentBranch($repositoryProvider);
    }

    public function gitPull(GitRepoProviderInterface $repositoryProvider): ToolInterface
    {
        return new Pull($repositoryProvider);
    }

    public function gitResetHard(GitRepoProviderInterface $repositoryProvider): ToolInterface
    {
        return new ResetHard($repositoryProvider);
    }

    public function gitAddFile(GitRepoProviderInterface $repositoryProvider): ToolInterface
    {
        return new AddFile($repositoryProvider);
    }

    public function gitUnstageFile(GitRepoProviderInterface $repositoryProvider): ToolInterface
    {
        return new UnstageFile($repositoryProvider);
    }

    public function gitCommit(GitRepoProviderInterface $repositoryProvider): ToolInterface
    {
        return new Commit($repositoryProvider);
    }

    public function gitPush(GitRepoProviderInterface $repositoryProvider): ToolInterface
    {
        return new Push($repositoryProvider);
    }

    public function gitGetStatus(GitRepoProviderInterface $repositoryProvider): ToolInterface
    {
        return new GetStatus($repositoryProvider);
    }
}
