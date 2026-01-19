<?php

namespace Anymodule\Agentmodule\Entity;

class Context
{
    public function __construct(
        public array $tasks,
        public array $payload = [],
    )
    {
    }

    public static function empty(): self
    {
        return new self(
            tasks: [],
            payload: [],
        );
    }

    public function updateTask(array $list): void
    {
        $this->tasks = $list;
    }

    public function getNamespace(string $namespace, array $default = []): array
    {
        $value = $this->payload[$namespace] ?? null;
        return is_array($value) ? $value : $default;
    }

    public function setNamespace(string $namespace, array $value): void
    {
        $this->payload[$namespace] = $value;
    }

    public function serialize(): array
    {
        return [
            'tasks' => $this->tasks,
            'payload' => $this->payload,
        ];
    }
}