<?php

namespace Anymodule\Agentmodule\Services\ApiService\Request\Validator;

/**
 * Интерфейс для валидаторов Agent API
 */
interface AgentApiValidatorInterface
{
    /**
     * Валидация agent_id
     */
    public function validateAgentId(string $agentId): void;

    /**
     * Валидация task_id
     */
    public function validateTaskId(int $taskId): void;

    /**
     * Валидация токена авторизации
     */
    public function validateAuthToken(string $authToken): void;

    /**
     * Валидация сообщений чата
     */
    public function validateChatMessages(array $chatMessages): void;

    /**
     * Валидация одного сообщения чата
     */
    public function validateChatMessage(mixed $message, int $index): void;

    /**
     * Валидация статистики токенов
     */
    public function validateTokenStats(array $tokenStats): void;

    /**
     * Валидация результата задачи
     */
    public function validateTaskResult(?string $result): void;

    /**
     * Проверка валидности UUID
     */
    public function isValidUuid(string $uuid): bool;

    /**
     * Валидация данных для создания сообщения чата
     */
    public function validateChatMessageData(string $role, string $content, ?string $id = null): void;

    /**
     * Валидация данных для создания статистики токенов
     */
    public function validateTokenStatsData(?int $promptTokens, ?int $completionTokens, ?int $totalTokens): void;
}
