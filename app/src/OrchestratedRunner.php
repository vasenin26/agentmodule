<?php

namespace Anymodule\Agentmodule;

use Anymodule\Agentmodule\Application\ResultHandlers\DocsModule;
use Anymodule\Agentmodule\Interface\Storage\StateStoreInterface;
use Anymodule\Agentmodule\Interface\Task\TaskApiInterface;
use Anymodule\Agentmodule\Interface\Task\TaskProcessorFactoryInterface;
use Anymodule\Agentmodule\Utils\Log;
use Ramsey\Uuid\UuidInterface;

/**
 * Orchestrated режим работы агента
 * 
 * Агент запускается оркестратором для обработки ОДНОЙ задачи.
 * Получает задачу через переменные окружения от оркестратора.
 * После обработки завершается с exit кодом 0 (success) или 1 (error).
 */
final class OrchestratedRunner
{
    const STORE_AGENT_STATUS_KEY = 'status';

    public function __construct(
        private TaskApiInterface              $api,
        private StateStoreInterface           $stateStore,
        private TaskProcessorFactoryInterface $processorFactory,
    )
    {
    }

    public function run(int $taskId, UuidInterface $agentUuid): void
    {
        $this->stateStore->push(self::STORE_AGENT_STATUS_KEY, 'started');

        Log::info("🚀 Agent started in ORCHESTRATED mode", [
            'agent_uuid' => $agentUuid,
            'task_id' => $taskId,
            'mode' => 'orchestrated',
        ]);

        try {
            // Получить полные данные задачи из External API
            Log::info("📥 Fetching task details from API", [
                'task_id' => $taskId,
                'agent_uuid' => $agentUuid,
            ]);
            
            $this->stateStore->push(self::STORE_AGENT_STATUS_KEY, 'fetching');
            $task = $this->api->getAgentTaskById($agentUuid, $taskId);

            if (is_null($task)) {
                Log::error("❌ Failed to fetch task - task not found or not assigned", [
                    'task_id' => $taskId,
                    'agent_uuid' => $agentUuid,
                ]);
                
                $this->stateStore->push(self::STORE_AGENT_STATUS_KEY, 'error');
                
                echo "ERROR: Task not found or not assigned to this agent\n";
                echo "Task ID: $taskId\n";
                echo "Agent UUID: $agentUuid\n";
                
                exit(1);
            }

            Log::info("✅ Task fetched successfully", [
                'task_id' => $task->id,
                'type' => $task->type ?? 'unknown',
                'project_id' => $task->projectId,
                'conversation_id' => $task->conversationId,
            ]);

            // Обработать задачу
            Log::info("⚙️ Processing task", [
                'task_id' => $task->id,
                'type' => $task->type,
            ]);

            $this->api->setStartProcessing($agentUuid, $taskId);
            $this->stateStore->push(self::STORE_AGENT_STATUS_KEY, 'processing');

            // Создать handler для обработки результата
            $handler = new DocsModule($this->api, $agentUuid, $task->id);
            
            // Обработать задачу через соответствующий процессор
            $this->processorFactory->createProcessorForTask($task)
                ->process($task, $handler);

            Log::info("✅ Task completed successfully", [
                'task_id' => $task->id,
                'agent_id' => $agentUuid,
            ]);
            
            $this->stateStore->push(self::STORE_AGENT_STATUS_KEY, 'completed');
            
            // Успешное завершение
            echo "SUCCESS: Task completed\n";
            echo "Task ID: {$task->id}\n";
            
            exit(0);

        } catch (\Throwable $e) {
            Log::exception($e, '❌ Task processing failed', [
                'agent_id' => $agentUuid,
                'agent_uuid' => $agentUuid,
                'task_id' => $taskId,
                'error_class' => get_class($e),
                'error_message' => $e->getMessage(),
            ]);
            
            $this->stateStore->push(self::STORE_AGENT_STATUS_KEY, 'failed');
            
            echo "ERROR: Task processing failed\n";
            echo "Task ID: $taskId\n";
            echo "Error: {$e->getMessage()}\n";
            
            // Завершение с ошибкой
            exit(1);
        }
    }
}

