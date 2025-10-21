<?php

namespace Anymodule\Agentmodule\Entity;

class Context
{
    public function __construct(
        public array $tasks
    )
    {
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