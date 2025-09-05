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

    public function build(): ToolsService
    {
        return $this->toolServiceFactory->withTools($this->tools);
    }
}