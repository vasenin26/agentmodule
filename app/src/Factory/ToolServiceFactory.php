<?php

namespace Anymodule\Agentmodule\Factory;


use Anymodule\Agentmodule\Interface\ToolServiceFactoryInterface;
use Anymodule\Agentmodule\Services\ToolsService\ToolsFactory;
use Anymodule\Agentmodule\Services\ToolsService\ToolsService;

class ToolServiceFactory implements ToolServiceFactoryInterface
{
    public function __construct(
        private ToolsFactory $factory
    )
    {
    }

    public function withMainTools(): ToolsService
    {
        return new ToolsService(
            $this->factory->sendResult(),
            [
                // Базовые утилиты
                'time' => $this->factory->time(),
                
//                // Существующие Git утилиты
//                'git-readFile' => $this->factory->gitReadFile(),
//                'git-searchFileByName' => $this->factory->gitSearchFileByName(),
//                'git-readDir' => $this->factory->gitReadDir(),
//
//                // Новые Git утилиты
//                'git-analyze-structure' => $this->factory->gitAnalyzeStructure(),
//                'git-get-dependencies' => $this->factory->gitGetDependencies(),
//                'git-search-pattern' => $this->factory->gitSearchPattern(),
//                'git-find-config-files' => $this->factory->gitFindConfigFiles(),
//                'git-analyze-classes' => $this->factory->gitAnalyzeClasses(),
            ]
        );
    }

    /**
     * Создать сервис со всеми утилитами включая Page утилиты для конкретного проекта
     */
    public function withAllToolsForProject(int $projectId): ToolsService
    {
        return new ToolsService(
            $this->factory->sendResult(),
            [
                // Базовые утилиты
                'time' => $this->factory->time(),
                
                // Существующие Git утилиты
                'git-readFile' => $this->factory->gitReadFile(),
                'git-searchFileByName' => $this->factory->gitSearchFileByName(),
                'git-readDir' => $this->factory->gitReadDir(),
                
                // Новые Git утилиты
                'git-analyze-structure' => $this->factory->gitAnalyzeStructure(),
                'git-get-dependencies' => $this->factory->gitGetDependencies(),
                'git-search-pattern' => $this->factory->gitSearchPattern(),
                'git-find-config-files' => $this->factory->gitFindConfigFiles(),
                'git-analyze-classes' => $this->factory->gitAnalyzeClasses(),
                
                // Page утилиты для конкретного проекта
                'page-get-info' => $this->factory->pageGetInfo($projectId),
                'page-get-attached-files' => $this->factory->pageGetAttachedFiles($projectId),
                'page-get-project-pages' => $this->factory->pageGetProjectPages($projectId),
                'page-get-hierarchy-tree' => $this->factory->pageGetHierarchyTree($projectId),
                'page-find-related-pages' => $this->factory->pageGetRelatedPages($projectId),
                'page-get-actualization-info' => $this->factory->pageGetActualizationInfo($projectId),
                'page-get-task-history' => $this->factory->pageGetTaskHistory($projectId),
            ]
        );
    }
}
