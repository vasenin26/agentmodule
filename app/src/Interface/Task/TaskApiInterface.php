<?php

namespace Anymodule\Agentmodule\Interface\Task;

use Anymodule\Agentmodule\Entity\ProcessingResult;
use Anymodule\Agentmodule\Entity\Task;
use Anymodule\Agentmodule\Entity\TaskState;
use Ramsey\Uuid\UuidInterface;

interface TaskApiInterface
{
    /**
     * Получить задачу для агента (standalone режим)
     * 
     * @param UuidInterface $agentId UUID агента
     * @return Task|null Задача или null если нет задач
     */
    public function getTask(UuidInterface $agentId): ?Task;

    /**
     * Получить задачу по UUID агента (orchestrated режим)
     *
     * Используется в orchestrated режиме когда оркестратор
     * уже зарезервировал задачу для агента.
     *
     * @param UuidInterface $agentUuid
     * @param int $taskId
     * @return Task|null Задача или null если задача не найдена
     */
    public function getAgentTaskById(UuidInterface $agentUuid, int $taskId): ?Task;

    /**
     * Отправить результат обработки задачи
     * 
     * @param UuidInterface $agentId UUID агента
     * @param int $taskId ID задачи
     * @param ProcessingResult $result Результат обработки
     * @return TaskState Состояние задачи
     */
    public function sendResult(UuidInterface $agentId, int $taskId, ProcessingResult $result): TaskState;

    public function setStartProcessing(UuidInterface $agentUuid, int $taskId);

    public function createSubtask(UuidInterface $agentUuid, int $taskId): int;
}