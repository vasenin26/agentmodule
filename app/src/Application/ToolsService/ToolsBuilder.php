<?php

namespace Anymodule\Agentmodule\Application\ToolsService;

use Anymodule\Agentmodule\Application\Tools\Tasks\TaskStorageInterface;
use Anymodule\Agentmodule\Factory\ToolServiceFactory;
use Anymodule\Agentmodule\Interface\Git\GitRepoProviderInterface;
use Anymodule\Agentmodule\Services\TaskStorage\TasksStorage;

class ToolsBuilder
{
    private array $tools = [];

    public function __construct(
        private ToolServiceFactory $toolServiceFactory,
        private ToolsFactory       $toolsFactory
    )
    {
    }

    public function withProject($projectId, string $prefix = 'page'): ToolsBuilder
    {
        return $this->withTools([
            $this->toolsFactory->pageGetInfo($projectId),
            $this->toolsFactory->pageGetAttachedFiles($projectId),
            $this->toolsFactory->pageGetProjectPages($projectId),
            $this->toolsFactory->pageGetHierarchyTree($projectId),
            $this->toolsFactory->pageGetRelatedPages($projectId),
//            $this->toolsFactory->pageGetActualizationInfo($projectId),
//            $this->toolsFactory->pageGetTaskHistory($projectId),
        ]);
    }

    public function withGit(GitRepoProviderInterface $repoProvider): ToolsBuilder
    {
        return $this->withTools([
            $this->toolsFactory->gitReadFile($repoProvider),
            $this->toolsFactory->gitReadFileLines($repoProvider),
            $this->toolsFactory->gitGrepFile($repoProvider),
            $this->toolsFactory->gitSearchFileByName($repoProvider),
            $this->toolsFactory->gitReadDir($repoProvider),
            $this->toolsFactory->gitAnalyzeStructure($repoProvider),
            $this->toolsFactory->gitGetDependencies($repoProvider),
            $this->toolsFactory->gitSearchPattern($repoProvider),
            $this->toolsFactory->gitFindConfigFiles($repoProvider),
//            $this->toolsFactory->gitAnalyzeClasses($repoProvider),
        ]);
    }

    public function withEditor(GitRepoProviderInterface $repoProvider, string $prefix = 'editor'): ToolsBuilder
    {
        return $this->withTools([
            $this->toolsFactory->editorDeleteLines($repoProvider),
            $this->toolsFactory->editorChangeLine($repoProvider),
//            $this->toolsFactory->editorEditFile($repoProvider),
            $this->toolsFactory->editorInsertLines($repoProvider),
//            $this->toolsFactory->editorReplaceInFile($repoProvider),
            $this->toolsFactory->editorInsertOrReplace($repoProvider),
        ]);
    }

    public function withTasks(TaskStorageInterface $tasksStorage): ToolsBuilder
    {
        return $this->withTools([
            $this->toolsFactory->tasksList($tasksStorage),
            $this->toolsFactory->tasksAdd($tasksStorage),
            $this->toolsFactory->tasksComplete($tasksStorage),
        ]);
    }

    public function withRepoManagement(GitRepoProviderInterface $repoProvider, string $prefix = 'git'): ToolsBuilder
    {
        return $this->withTools([
            $this->toolsFactory->gitGetCurrentBranch($repoProvider),
            $this->toolsFactory->gitPull($repoProvider),
            $this->toolsFactory->gitResetHard($repoProvider),
            $this->toolsFactory->gitAddFile($repoProvider),
            $this->toolsFactory->gitUnstageFile($repoProvider),
            $this->toolsFactory->gitCommit($repoProvider),
            $this->toolsFactory->gitPush($repoProvider),
            $this->toolsFactory->gitGetStatus($repoProvider),
        ]);
    }

    public function withTerminal(): ToolsBuilder
    {
        return $this->withTools([
            $this->toolsFactory->terminalRun(),
        ]);
    }

    public function build(): ToolsProviderService
    {
        return $this->toolServiceFactory->withTools($this->tools);
    }

    public function withTools(array $tools): ToolsBuilder
    {
        foreach ($tools as $tool) {
            $name = $tool->getName();
            
            if (isset($this->tools[$name])) {
                $existingClass = get_class($this->tools[$name]);
                $newClass = get_class($tool);
                throw new \InvalidArgumentException(
                    "Tool with name '$name' already exists. " .
                    "Existing: $existingClass, New: $newClass. " .
                    "Create wrapper class with different name to avoid conflicts."
                );
            }
            
            $this->tools[$name] = $tool;
        }
        return $this;
    }
}