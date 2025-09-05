<?php

namespace Anymodule\Agentmodule\Services\ApiService;

use Anymodule\Agentmodule\Entity\LLMResult;
use Anymodule\Agentmodule\Entity\Task;
use Anymodule\Agentmodule\Interface\PageApi;
use Anymodule\Agentmodule\Interface\TaskApi;
use Anymodule\Agentmodule\Services\ApiService\Request\Tasks\GetAgentTask;
use Anymodule\Agentmodule\Services\ApiService\Request\Tasks\UpdateAgentTask;
use Ramsey\Uuid\UuidInterface;

class ApiService implements TaskApi, PageApi
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
            $taskData->task_id,
            $taskData->messages,
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
        );

        $request->exec($this->api);
    }
}