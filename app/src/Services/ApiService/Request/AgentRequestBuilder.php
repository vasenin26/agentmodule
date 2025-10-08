<?php

namespace Anymodule\Agentmodule\Services\ApiService\Request;

use Anymodule\Agentmodule\Services\ApiService\Request\Tasks\GetTask;
use Anymodule\Agentmodule\Services\ApiService\Request\Tasks\GetAgentTaskDetails;
use Anymodule\Agentmodule\Services\ApiService\Request\Tasks\UpdateAgentTask;
use Anymodule\Agentmodule\Services\ApiService\Request\Admin\GetAgentStats;
use Anymodule\Agentmodule\Services\ApiService\Request\Admin\GetStuckTasks;
use Anymodule\Agentmodule\Services\ApiService\Request\Admin\ResetStuckTasks;
use Anymodule\Agentmodule\Services\ApiService\Request\Validator\AgentApiValidatorInterface;

/**
 * Фабрика для создания запросов к Agent API с валидацией
 */
class AgentRequestBuilder
{
    public function __construct(
        private AgentApiValidatorInterface $validator
    ) {
    }

    /**
     * Создать запрос на получение задачи для агента
     */
    public function getTask(string $agentId): GetTask
    {
        $this->validator->validateAgentId($agentId);
        
        return new GetTask($agentId);
    }

    /**
     * Создать запрос на получение деталей задачи
     */
    public function getTaskDetails(int $taskId, string $agentId): GetAgentTaskDetails
    {
        $this->validator->validateTaskId($taskId);
        $this->validator->validateAgentId($agentId);
        
        return new GetAgentTaskDetails($taskId, $agentId);
    }

    /**
     * Создать запрос на обновление задачи
     */
    public function updateTask(
        int $taskId,
        string $agentId,
        array $chatMessages,
        array $tokenStats,
        ?string $result = null
    ): UpdateAgentTask {
        $this->validator->validateTaskId($taskId);
        $this->validator->validateAgentId($agentId);
        $this->validator->validateChatMessages($chatMessages);
        $this->validator->validateTokenStats($tokenStats);
        $this->validator->validateTaskResult($result);
        
        return new UpdateAgentTask($taskId, $agentId, $chatMessages, $tokenStats, $result);
    }

    /**
     * Создать запрос на получение статистики агентов (админ)
     */
    public function getAgentStats(string $authToken): GetAgentStats
    {
        $this->validator->validateAuthToken($authToken);
        
        return new GetAgentStats($authToken);
    }

    /**
     * Создать запрос на получение зависших задач (админ)
     */
    public function getStuckTasks(string $authToken): GetStuckTasks
    {
        $this->validator->validateAuthToken($authToken);
        
        return new GetStuckTasks($authToken);
    }

    /**
     * Создать запрос на сброс зависших задач (админ)
     */
    public function resetStuckTasks(string $authToken): ResetStuckTasks
    {
        $this->validator->validateAuthToken($authToken);
        
        return new ResetStuckTasks($authToken);
    }


}
