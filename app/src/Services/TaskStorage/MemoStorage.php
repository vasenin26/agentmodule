<?php

namespace Anymodule\Agentmodule\Services\TaskStorage;

use Anymodule\Agentmodule\Application\Tools\Tasks\TaskStorageInterface;

class MemoStorage implements TaskStorageInterface
{
    /**
     * In-memory storage for tasks
     * @var array{tasks: array<int, array{id:int,title:string,done:bool}>, lastId:int}
     */
    private array $storage;

    public function __construct(array $tasks = [])
    {
        $lastId = -1;

        foreach ($tasks as $task) {
            if ($task['id'] > $lastId) {
                $lastId = $task['id'];
            }
        }

        $this->storage = ['tasks' => $tasks, 'lastId' => ++$lastId];
    }

    /**
     * @return array<int, array{id:int,title:string,done:bool}>
     */
    public function list(): array
    {
        return array_values($this->storage['tasks']);
    }

    /**
     * @param array<int, string> $titles
     * @return array<int, array{id:int,title:string,done:bool}>
     */
    public function addMany(array $titles): array
    {
        $created = [];
        foreach ($titles as $title) {
            $this->storage['lastId']++;
            $task = [
                'id' => $this->storage['lastId'],
                'title' => (string)$title,
                'done' => false,
            ];
            $this->storage['tasks'][] = $task;
            $created[] = $task;
        }
        return $created;
    }

    public function complete(int $id): ?array
    {
        foreach ($this->storage['tasks'] as &$task) {
            if ($task['id'] === $id) {
                $task['done'] = true;
                return $task;
            }
        }
        return null;
    }

    /**
     * @return array{total:int,completed:int,remaining:int}
     */
    public function getStats(): array
    {
        $total = count($this->storage['tasks']);
        $completed = 0;

        foreach ($this->storage['tasks'] as $task) {
            if ($task['done']) {
                $completed++;
            }
        }

        return [
            'total' => $total,
            'completed' => $completed,
            'remaining' => $total - $completed
        ];
    }

    /**
     * Clear all tasks and reset storage
     */
    public function clear(): void
    {
        $this->storage = ['tasks' => [], 'lastId' => 0];
    }
}