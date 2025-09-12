<?php

namespace Anymodule\Agentmodule\Services\ApiService;

use Anymodule\Agentmodule\Entity\LLMResult;
use Anymodule\Agentmodule\Entity\Page;
use Anymodule\Agentmodule\Entity\Task;
use Anymodule\Agentmodule\Interface\Git\GitTokenProviderInterface;
use Anymodule\Agentmodule\Interface\Page\PageApi;
use Anymodule\Agentmodule\Interface\Task\TaskApi;
use Anymodule\Agentmodule\Interface\TokenProviderInterface;
use Anymodule\Agentmodule\Services\ApiService\Request\Tasks\GetAgentTask;
use Anymodule\Agentmodule\Services\ApiService\Request\Tasks\UpdateAgentTask;
use Anymodule\Agentmodule\Services\ApiService\Request\Pages\GetPage;
use Anymodule\Agentmodule\Services\ApiService\Request\Pages\GetAllProjectPages;
use Anymodule\Agentmodule\Services\ApiService\Request\Pages\GetPageHierarchy;
use Anymodule\Agentmodule\Services\ApiService\Request\Pages\GetPageChildren;
use Anymodule\Agentmodule\Services\ApiService\Request\Pages\GetPageParent;
use Anymodule\Agentmodule\Services\ApiService\Request\Pages\FindRelatedPages;
use Anymodule\Agentmodule\Services\ApiService\Request\Pages\GetPageWithActualization;
use Anymodule\Agentmodule\Services\ApiService\Request\Pages\GetPageFiles;
use Anymodule\Agentmodule\Services\ApiService\Request\Pages\GetTaskHistory;
use Anymodule\Agentmodule\Services\ApiService\Response\Pages\PageDTO;
use Anymodule\Agentmodule\Services\ApiService\Response\Pages\PageListDTO;
use Anymodule\Agentmodule\Services\ApiService\Response\Pages\PageHierarchyDTO;
use Anymodule\Agentmodule\Services\ApiService\Response\Pages\PageFilesDTO;
use Anymodule\Agentmodule\Services\ApiService\Response\Pages\TaskHistoryDTO;
use Ramsey\Uuid\UuidInterface;

class Service implements TaskApi, PageApi
{
    private ApiClient $api;

    public function __construct(
        string         $host,
        private string $token,
    )
    {
        $this->api = new ApiClient($host);
    }

    public function getTask(UuidInterface $agentId): ?Task
    {
        $request = new GetAgentTask($this->token, $agentId->toString());
        $taskData = $request->exec($this->api);

        if (is_null($taskData)) {
            return null;
        }

        return new Task(
            id: $taskData->task_id,
            messages: $taskData->messages,
            projectId: $taskData->project_id,
            resultRequired: $taskData->resulRequired
        );
    }

    public function sendResult(UuidInterface $agentId, int $taskId, LLMResult $result): void
    {
        $request = new UpdateAgentTask(
            token: $this->token,
            taskId: $taskId,
            agentId: $agentId->toString(),
            chatMessages: $result->messages,
            tokenStats: UpdateAgentTask::createTokenStats(
                promptTokens: $result->prompt_tokens,
                completionTokens: $result->completion_tokens,
                totalTokens: $result->total_tokens
            ),
            result: $result->answer,
            completed: $result->completed
        );

        $request->exec($this->api);
    }

    // PageApi methods implementation

    public function getPageById(int $pageId): Page
    {
        $request = new GetPage($pageId);
        $pageData = $request->exec($this->api);

        return new Page(
            id: $pageData->id,
            title: $pageData->title,
            content: $pageData->content,
            files: $pageData->files
        );
    }

    public function getAllProjectPages(int $projectId): array
    {
        $request = new GetAllProjectPages($this->token);
        $response = $request->exec($this->api);

        return $response->getPages();
    }

    public function getPageHierarchy(int $projectId, ?int $rootPageId = null): array
    {
        $request = new GetPageHierarchy($this->token, $rootPageId);
        $response = $request->exec($this->api);

        return $response->getHierarchy();
    }

    public function getPageChildren(int $pageId): array
    {
        $request = new GetPageChildren($pageId, $this->token);
        $response = $request->exec($this->api);

        return $response->getChildren();
    }

    public function getPageParent(int $pageId): ?Page
    {
        $request = new GetPageParent($pageId, $this->token);
        $response = $request->exec($this->api);
        $pageData = $response->getPage();

        if ($pageData === null) {
            return null;
        }

        return new Page(
            id: $pageData->id,
            title: $pageData->title,
            content: $pageData->content,
            files: $pageData->files
        );
    }

    public function findRelatedPages(int $pageId): array
    {
        $request = new FindRelatedPages($pageId, $this->token);
        $response = $request->exec($this->api);

        return $response->getRelated();
    }

    public function getPageWithActualization(int $pageId): ?Page
    {
        $request = new GetPageWithActualization($pageId, $this->token);
        $pageData = $request->exec($this->api);

        return new Page(
            id: $pageData->id,
            title: $pageData->title,
            content: $pageData->content,
            files: $pageData->files
        );
    }

    public function getPageFiles(int $pageId): array
    {
        $request = new GetPageFiles($pageId, $this->token);
        $response = $request->exec($this->api);

        return $response->files;
    }

    public function getTaskHistory(int $pageId): array
    {
        $request = new GetTaskHistory($pageId, $this->token);
        $response = $request->exec($this->api);

        return $response->getTasks();
    }

    public function validatePageAccess(int $pageId, int $projectId): bool
    {
        try {
            $page = $this->getPageById($pageId);
            // В реальной реализации здесь должна быть проверка принадлежности страницы проекту
            // Пока возвращаем true, если страница найдена
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function isPageInProject(int $pageId, int $projectId): bool
    {
        return $this->validatePageAccess($pageId, $projectId);
    }
}