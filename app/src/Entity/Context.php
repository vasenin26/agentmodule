<?php

namespace Anymodule\Agentmodule\Entity;

class Context
{
    public function __construct(
        public array $tasks
    )
    {
    }

    public static function empty(): self
    {
        return new self(
            tasks: []
        );
    }

    public function updateTask(array $list): void
    {
        $this->tasks = $list;
    }

    public function serialize(): array
    {
        return [
            'tasks' => $this->tasks,
        ];
    }
}