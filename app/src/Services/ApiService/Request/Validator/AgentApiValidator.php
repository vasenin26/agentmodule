<?php

namespace Anymodule\Agentmodule\Services\ApiService\Request\Validator;

/**
 * Валидатор для Agent API запросов
 * Содержит все правила валидации данных для взаимодействия с Agent API
 */
class AgentApiValidator implements AgentApiValidatorInterface
{
    /**
     * Валидация agent_id
     */
    public function validateAgentId(string $agentId): void
    {
        if (empty($agentId)) {
            throw new \InvalidArgumentException('Agent ID is required');
        }
        
        if (strlen($agentId) > 36) {
            throw new \InvalidArgumentException('Agent ID cannot exceed 36 characters');
        }
        
        if (!$this->isValidUuid($agentId)) {
            throw new \InvalidArgumentException('Agent ID must be a valid UUID');
        }
    }

    /**
     * Валидация task_id
     */
    public function validateTaskId(int $taskId): void
    {
        if ($taskId <= 0) {
            throw new \InvalidArgumentException('Task ID must be a positive integer');
        }
    }

    /**
     * Валидация токена авторизации
     */
    public function validateAuthToken(string $authToken): void
    {
        if (empty($authToken)) {
            throw new \InvalidArgumentException('Auth token is required for admin requests');
        }
    }

    /**
     * Валидация сообщений чата
     */
    public function validateChatMessages(array $chatMessages): void
    {
        if (empty($chatMessages)) {
            throw new \InvalidArgumentException('At least one chat message is required');
        }
        
        if (count($chatMessages) > 1000) {
            throw new \InvalidArgumentException('Too many chat messages (max: 1000)');
        }
        
        foreach ($chatMessages as $index => $message) {
            $this->validateChatMessage($message, $index);
        }
    }

    /**
     * Валидация одного сообщения чата
     */
    public function validateChatMessage(mixed $message, int $index): void
    {
        if (!is_array($message)) {
            throw new \InvalidArgumentException("Chat message at index {$index} must be an array");
        }
        
        if (!isset($message['role']) || !isset($message['content'])) {
            throw new \InvalidArgumentException("Chat message at index {$index} must have 'role' and 'content' fields");
        }
        
        $this->validateChatMessageRole($message['role'], $index);
        $this->validateChatMessageContent($message['content'], $index);
        
        // Валидация опционального ID сообщения
        if (isset($message['id'])) {
            $this->validateChatMessageId($message['id'], $index);
        }
    }

    /**
     * Валидация роли сообщения
     */
    public function validateChatMessageRole(string $role, int $index): void
    {
        $allowedRoles = ['user', 'assistant', 'system'];
        
        if (!in_array($role, $allowedRoles)) {
            throw new \InvalidArgumentException(
                "Invalid role '{$role}' at index {$index}. Must be: " . implode(', ', $allowedRoles)
            );
        }
    }

    /**
     * Валидация контента сообщения
     */
    public function validateChatMessageContent(string $content, int $index): void
    {
        if (empty(trim($content))) {
            throw new \InvalidArgumentException("Chat message content at index {$index} cannot be empty");
        }
        
        if (strlen($content) > 65535) {
            throw new \InvalidArgumentException("Chat message content at index {$index} is too long (max: 65535 characters)");
        }
    }

    /**
     * Валидация ID сообщения
     */
    public function validateChatMessageId(string $messageId, int $index): void
    {
        if (strlen($messageId) > 255) {
            throw new \InvalidArgumentException("Chat message ID at index {$index} is too long (max: 255 characters)");
        }
    }

    /**
     * Валидация статистики токенов
     */
    public function validateTokenStats(array $tokenStats): void
    {
        $allowedKeys = ['prompt_tokens', 'completion_tokens', 'total_tokens'];

        foreach ($tokenStats as $key => $value) {
            if (!in_array($key, $allowedKeys)) {
                throw new \InvalidArgumentException("Unknown token stat key: {$key}");
            }

            if ($value !== null) {
                $this->validateTokenValue($key, $value);
            }
        }

        $this->validateTokenConsistency($tokenStats);
    }

    /**
     * Валидация значения токена
     */
    private function validateTokenValue(string $key, mixed $value): void
    {
        if (!is_int($value) || $value < 0) {
            throw new \InvalidArgumentException("Token stat '{$key}' must be a non-negative integer or null");
        }
    }

    /**
     * Валидация консистентности токенов
     */
    private function validateTokenConsistency(array $tokenStats): void
    {
        $prompt = $tokenStats['prompt_tokens'] ?? null;
        $completion = $tokenStats['completion_tokens'] ?? null;
        $total = $tokenStats['total_tokens'] ?? null;
        
        if ($prompt !== null && $completion !== null && $total !== null) {
            if ($total < ($prompt + $completion)) {
                throw new \InvalidArgumentException('Total tokens cannot be less than sum of prompt and completion tokens');
            }
        }
    }

    /**
     * Валидация результата задачи
     */
    public function validateTaskResult(?string $result): void
    {
        if ($result !== null && strlen($result) > 16777215) {
            throw new \InvalidArgumentException('Result is too long (max: 16MB)');
        }
    }

    /**
     * Проверка валидности UUID v4
     */
    public function isValidUuid(string $uuid): bool
    {
        return preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i', $uuid) === 1;
    }

    /**
     * Валидация массива данных для создания сообщения чата
     */
    public function validateChatMessageData(string $role, string $content, ?string $id = null): void
    {
        $this->validateChatMessageRole($role, 0);
        $this->validateChatMessageContent($content, 0);
        
        if ($id !== null) {
            $this->validateChatMessageId($id, 0);
        }
    }

    /**
     * Валидация массива данных для создания статистики токенов
     */
    public function validateTokenStatsData(?int $promptTokens, ?int $completionTokens, ?int $totalTokens): void
    {
        $stats = array_filter([
            'prompt_tokens' => $promptTokens,
            'completion_tokens' => $completionTokens,
            'total_tokens' => $totalTokens
        ], fn($value) => $value !== null);
        
        if (!empty($stats)) {
            $this->validateTokenStats($stats);
        }
    }

    /**
     * Валидация данных для создания подзадачи
     */
    public function validateSubtaskData(string $type, string $agentUuid): void
    {
        if (empty(trim($type))) {
            throw new \InvalidArgumentException('Task type is required');
        }
        
        if (strlen($type) > 255) {
            throw new \InvalidArgumentException('Task type cannot exceed 255 characters');
        }
        
        $this->validateAgentId($agentUuid);
    }
}
