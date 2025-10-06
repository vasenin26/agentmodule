<?php

namespace Anymodule\Agentmodule;

use Anymodule\Agentmodule\Interface\StateStoreInterface;
use Anymodule\Agentmodule\Interface\Task\TaskApi;
use Anymodule\Agentmodule\Interface\Task\TaskProcessorFactoryInterface;
use Anymodule\Agentmodule\ResultHandlers\DocsModule;
use Anymodule\Agentmodule\Utils\Log;

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
        private TaskApi                       $api,
        private StateStoreInterface           $stateStore,
        private TaskProcessorFactoryInterface $processorFactory,
    )
    {
    }

    public function run(): void
    {
        // Получить переменные окружения от оркестратора
        $taskId = getenv('TASK_ID');
        $agentUuid = getenv('AGENT_UUID');
        $agentId = getenv('AGENT_ID');

        // Валидация обязательных переменных
        if (!$taskId || !$agentUuid || !$agentId) {
            Log::error('Missing required environment variables for orchestrated mode', [
                'TASK_ID' => $taskId ?: 'not set',
                'AGENT_UUID' => $agentUuid ?: 'not set',
                'AGENT_ID' => $agentId ?: 'not set',
            ]);
            
            echo "ERROR: Missing required environment variables\n";
            echo "Required: TASK_ID, AGENT_UUID, AGENT_ID\n";
            
            exit(1);
        }

        $this->stateStore->push(self::STORE_AGENT_STATUS_KEY, 'started');

        Log::info("🚀 Agent started in ORCHESTRATED mode", [
            'agent_id' => $agentId,
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
            $task = $this->api->getTaskByUuid($agentUuid);

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
            
            $this->stateStore->push(self::STORE_AGENT_STATUS_KEY, 'processing');

            // Создать handler для обработки результата
            $handler = new DocsModule($this->api, $agentId, $task);
            
            // Обработать задачу через соответствующий процессор
            $this->processorFactory->createProcessorForTask($task)
                ->process($task, $handler);

            Log::info("✅ Task completed successfully", [
                'task_id' => $task->id,
                'agent_id' => $agentId,
            ]);
            
            $this->stateStore->push(self::STORE_AGENT_STATUS_KEY, 'completed');
            
            // Успешное завершение
            echo "SUCCESS: Task completed\n";
            echo "Task ID: {$task->id}\n";
            
            exit(0);

        } catch (\Throwable $e) {
            Log::exception($e, '❌ Task processing failed', [
                'agent_id' => $agentId,
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

