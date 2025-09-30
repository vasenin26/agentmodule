<?php

namespace Anymodule\Agentmodule\Services\ToolsService;

use Anymodule\Agentmodule\Factory\ToolServiceFactory;
use Anymodule\Agentmodule\Tools\Tasks\ListTasks;
use Anymodule\Agentmodule\Tools\Tasks\TasksStorage;

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
            $this->toolsFactory->pageGetActualizationInfo($projectId),
            $this->toolsFactory->pageGetTaskHistory($projectId),
        ]);
    }

    public function withGit(string $prefix = 'git'): ToolsBuilder
    {
        return $this->withTools([
            $this->toolsFactory->gitReadFile(),
            $this->toolsFactory->gitReadFileLines(),
            $this->toolsFactory->gitGrepFile(),
            $this->toolsFactory->gitSearchFileByName(),
            $this->toolsFactory->gitReadDir(),
            $this->toolsFactory->gitAnalyzeStructure(),
            $this->toolsFactory->gitGetDependencies(),
            $this->toolsFactory->gitSearchPattern(),
            $this->toolsFactory->gitFindConfigFiles(),
            $this->toolsFactory->gitAnalyzeClasses(),
        ]);
    }

    public function withEditor(string $prefix = 'editor'): ToolsBuilder
    {
        return $this->withTools([
            $this->toolsFactory->editorEditFile(),
            $this->toolsFactory->editorReplaceInFile(),
            $this->toolsFactory->editorInsertOrReplace(),
        ]);
    }

    public function withTasks(TasksStorage $tasksStorage): ToolsBuilder
    {
        return $this->withTools([
            $this->toolsFactory->tasksList($tasksStorage),
            $this->toolsFactory->tasksAdd($tasksStorage),
            $this->toolsFactory->tasksComplete($tasksStorage),
        ]);
    }

    public function withRepoManagement(string $prefix = 'git'): ToolsBuilder
    {
        return $this->withTools([
            $this->toolsFactory->gitGetCurrentBranch(),
            $this->toolsFactory->gitPull(),
            $this->toolsFactory->gitResetHard(),
            $this->toolsFactory->gitAddFile(),
            $this->toolsFactory->gitUnstageFile(),
            $this->toolsFactory->gitCommit(),
            $this->toolsFactory->gitPush(),
            $this->toolsFactory->gitGetStatus(),
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