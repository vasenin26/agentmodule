<?php

namespace Anymodule\Agentmodule\Services\ApiService;

use Anymodule\Agentmodule\Entity\ProcessingResult;
use Anymodule\Agentmodule\Entity\Page;
use Anymodule\Agentmodule\Entity\PageVersion;
use Anymodule\Agentmodule\Entity\TaskState;
use Anymodule\Agentmodule\Services\ApiService\Exception\RequestException;
use Anymodule\Agentmodule\Services\ApiService\Request\Pages\GetPageVersion;
use Anymodule\Agentmodule\Services\ApiService\Request\Tasks\GetTaskById;
use Anymodule\Agentmodule\Services\ApiService\Request\Tasks\ProcessingAgentTask;
use Anymodule\Agentmodule\Services\ApiService\Response\Pages\PageVersionDTO;
use Anymodule\Agentmodule\Entity\Task;
use Anymodule\Agentmodule\Interface\Git\GitTokenProviderInterface;
use Anymodule\Agentmodule\Interface\Page\PageApi;
use Anymodule\Agentmodule\Interface\Task\TaskApi;
use Anymodule\Agentmodule\Interface\TokenProviderInterface;
use Anymodule\Agentmodule\Services\ApiService\Request\Tasks\GetTask;
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
use Anymodule\Agentmodule\Utils\Log;
use Ramsey\Uuid\UuidInterface;

class Service implements TaskApi, PageApi
{
    private ApiClient $api;

    public function __construct(
        string         $host,
        private string $token,
    )
    {
        if (empty($this->token)) {
            throw new \Exception("Token not set");
        }

        $this->api = new ApiClient($host);
    }

    public function getTask(UuidInterface $agentId): ?Task
    {
        $request = new GetTask($this->token, $agentId->toString());
        $taskData = $request->exec($this->api);

        if (is_null($taskData)) {
            return null;
        }

        return new Task(
            id: $taskData->task_id,
            type: $taskData->type,
            conversationId: $taskData->chat_id,
            messages: $taskData->messages,
            projectId: $taskData->project_id,
            resultRequired: $taskData->resulRequired
        );
    }

    /**
     * Получить задачу по UUID агента (orchestrated режим)
     *
     * Endpoint: POST /api/agent/task
     * Body: {"agent_uuid": "uuid-456"}
     *
     * Ответ от API:
     * {
     *   "id": 123,
     *   "type": "documentation",
     *   "agent_uuid": "uuid-456",
     *   "project_id": 789,
     *   "result_required": true,
     *   "chat": {
     *     "id": 456,
     *     "messages": [...]
     *   }
     * }
     *
     * Используется в orchestrated режиме когда оркестратор
     * уже зарезервировал задачу для агента.
     *
     * @param UuidInterface $agentUuid UUID агента от оркестратора
     * @param int $taskId
     * @return Task|null Задача или null если не найдена
     * @throws RequestException
     */
    public function getAgentTaskById(UuidInterface $agentUuid, int $taskId): ?Task
    {
        Log::info("🔍 Requesting task by UUID", [
            'agent_uuid' => $agentUuid,
            'mode' => 'orchestrated',
        ]);

        // Используем существующий GetAgentTask request
        // Он уже правильно настроен!
        $request = new GetTaskById($this->token, $taskId, $agentUuid);
        $taskData = $request->exec($this->api);

        if (is_null($taskData)) {
            Log::warning("⚠️ Task not found for agent UUID", [
                'agent_uuid' => $agentUuid,
            ]);

            return null;
        }

        Log::info("✅ Task received from API", [
            'task_id' => $taskData->task_id,
            'type' => $taskData->type ?? 'unknown',
            'project_id' => $taskData->project_id,
            'chat_id' => $taskData->chat_id,
            'messages_count' => count($taskData->messages),
        ]);

        // Маппинг в Task entity (как в getTask)
        return new Task(
            id: $taskData->task_id,
            type: $taskData->type,
            conversationId: $taskData->chat_id,
            messages: $taskData->messages,
            projectId: $taskData->project_id,
            resultRequired: $taskData->resulRequired
        );
    }

    public function setStartProcessing(UuidInterface $agentUuid, int $taskId)
    {
        Log::info("🔍 Requesting task by UUID", [
            'agent_uuid' => $agentUuid,
            'mode' => 'orchestrated',
        ]);

        // Используем существующий GetAgentTask request
        // Он уже правильно настроен!
        $request = new ProcessingAgentTask($this->token, $taskId, $agentUuid);
        $taskData = $request->exec($this->api);

        if (is_null($taskData)) {
            Log::warning("⚠️ Task not found for agent UUID", [
                'agent_uuid' => $agentUuid,
            ]);

            return null;
        }

        Log::info("✅ Task marked as processed", [
            'task_id' => $taskId,
        ]);
    }

    public function sendResult(UuidInterface $agentId, int $taskId, ProcessingResult $result): TaskState
    {
        $request = new UpdateAgentTask(
            token: $this->token,
            taskId: $taskId,
            agentId: $agentId->toString(),
            chatMessages: $result->conversation->serialize(),
            tokenStats: UpdateAgentTask::createTokenStats(
                promptTokens: $result->promptTokens,
                completionTokens: $result->completionTokens,
                totalTokens: $result->totalTokens
            ),
            completed: $result->completed,
            result: $result->answer
        );

        $response = $request->exec($this->api);

        return new TaskState(
            status: $response->status,
        );
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

    /**
     * Получение версии страницы по идентификатору версии.
     * Декларируем ожидаемый HTTP-запрос:
     * GET /api/projects/{projectId}/pages/versions/{versionId}
     * Ожидаемый ответ:
     * {
     *   "title": "string",
     *   "content": "string",
     *   "pageId": 123,
     *   "versionId": "string",
     *   "previousVersionId": "string|null"
     * }
     */
    public function getPageVersion(int $projectId, string $versionId): PageVersion
    {
        $request = new GetPageVersion($this->token, $versionId);
        /** @var PageVersionDTO $data */
        $data = $request->exec($this->api);

        return new PageVersion(
            title: $data->title,
            content: $data->content,
            pageId: $data->pageId,
            versionId: $data->versionId,
            previousVersionId: $data->previousVersionId,
        );
    }
}