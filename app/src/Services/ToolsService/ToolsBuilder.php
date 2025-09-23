<?php

namespace Anymodule\Agentmodule\Services\ToolsService;

use Anymodule\Agentmodule\Factory\ToolServiceFactory;

class ToolsBuilder
{
    private array $tools = [];

    public function __construct(
        private ToolServiceFactory $toolServiceFactory,
        private ToolsFactory $toolsFactory
    )
    {
    }

    public function withProject($projectId, string $prefix = 'page'): ToolsBuilder
    {
        $this->tools = array_merge($this->tools, [
            $prefix . '-get-info' => $this->toolsFactory->pageGetInfo($projectId),
            $prefix . '-get-attached-files' => $this->toolsFactory->pageGetAttachedFiles($projectId),
            $prefix . '-get-project-pages' => $this->toolsFactory->pageGetProjectPages($projectId),
            $prefix . '-get-hierarchy-tree' => $this->toolsFactory->pageGetHierarchyTree($projectId),
            $prefix . '-find-related-pages' => $this->toolsFactory->pageGetRelatedPages($projectId),
            $prefix . '-get-actualization-info' => $this->toolsFactory->pageGetActualizationInfo($projectId),
            $prefix . '-get-task-history' => $this->toolsFactory->pageGetTaskHistory($projectId),
        ]);

        return $this;
    }

    public function withGit(string $prefix = 'git'): ToolsBuilder
    {
        $this->tools = array_merge($this->tools, [
                $prefix . '-readFile' => $this->toolsFactory->gitReadFile(),
                $prefix . '-read-file-lines' => $this->toolsFactory->gitReadFileLines(),
                $prefix . '-grep-file' => $this->toolsFactory->gitGrepFile(),
                $prefix . '-searchFileByName' => $this->toolsFactory->gitSearchFileByName(),
                $prefix . '-readDir' => $this->toolsFactory->gitReadDir(),
                $prefix . '-analyze-structure' => $this->toolsFactory->gitAnalyzeStructure(),
                $prefix . '-get-dependencies' => $this->toolsFactory->gitGetDependencies(),
                $prefix . '-search-pattern' => $this->toolsFactory->gitSearchPattern(),
                $prefix . '-find-config-files' => $this->toolsFactory->gitFindConfigFiles(),
                $prefix . '-analyze-classes' => $this->toolsFactory->gitAnalyzeClasses(),
        ]);

        return $this;
    }

    public function withEditor(string $prefix = 'editor'): ToolsBuilder
    {
        $this->tools = array_merge($this->tools, [
            $prefix . '-edit-file' => $this->toolsFactory->editorEditFile(),
            $prefix . '-replace-in-file' => $this->toolsFactory->editorReplaceInFile(),
            $prefix . '-insert-or-replace' => $this->toolsFactory->editorInsertOrReplace(),
        ]);

        return $this;
    }

    public function withTasks(): ToolsBuilder
    {
        $this->tools = array_merge($this->tools, [
            'tasks-list' => $this->toolsFactory->tasksList(),
            'tasks-add' => $this->toolsFactory->tasksAdd(),
            'tasks-complete' => $this->toolsFactory->tasksComplete(),
        ]);

        return $this;
    }

    public function build(): ToolsService
    {
        return $this->toolServiceFactory->withTools($this->tools);
    }
}