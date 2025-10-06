<?php

namespace Anymodule\Agentmodule\Interface\Task;

use Anymodule\Agentmodule\Entity\ProcessingResult;
use Anymodule\Agentmodule\Entity\Task;
use Anymodule\Agentmodule\Entity\TaskState;
use Ramsey\Uuid\UuidInterface;

interface TaskApi
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
     * @param string $agentUuid UUID агента от оркестратора
     * @return Task|null Задача или null если задача не найдена
     */
    public function getTaskByUuid(string $agentUuid): ?Task;

    /**
     * Отправить результат обработки задачи
     * 
     * @param UuidInterface $agentId UUID агента
     * @param int $taskId ID задачи
     * @param ProcessingResult $result Результат обработки
     * @return TaskState Состояние задачи
     */
    public function sendResult(UuidInterface $agentId, int $taskId, ProcessingResult $result): TaskState;
}