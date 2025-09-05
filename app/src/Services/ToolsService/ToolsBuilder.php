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

    public function withGit($gitToken, string $prefix = 'git'): ToolsBuilder
    {
        $repositoryClient = new GitRepositoryClient($gitToken);

        $this->tools = array_merge($this->tools, [
                $prefix . '-readFile' => $this->toolsFactory->gitReadFile($repositoryClient),
                $prefix . '-searchFileByName' => $this->toolsFactory->gitSearchFileByName($repositoryClient),
                $prefix . '-readDir' => $this->toolsFactory->gitReadDir($repositoryClient),
                $prefix . '-analyze-structure' => $this->toolsFactory->gitAnalyzeStructure($repositoryClient),
                $prefix . '-get-dependencies' => $this->toolsFactory->gitGetDependencies($repositoryClient),
                $prefix . '-search-pattern' => $this->toolsFactory->gitSearchPattern($repositoryClient),
                $prefix . '-find-config-files' => $this->toolsFactory->gitFindConfigFiles($repositoryClient),
                $prefix . '-analyze-classes' => $this->toolsFactory->gitAnalyzeClasses($repositoryClient),
        ]);

        return $this;
    }

    public function build(): ToolsService
    {
        return $this->toolServiceFactory->withTools($this->tools);
    }
}