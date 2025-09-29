<?php

namespace Anymodule\Agentmodule\Tools\Tasks;

use Anymodule\Agentmodule\Utils\Log;

class TasksStorage
{
    /**
     * Per-instance in-memory state.
     * @var array{tasks: array<int, array{id:int,title:string,done:bool}>, lastId:int}
     */
    private array $state;

    public function __construct()
    {
        // keep signature for BC; storage is now in-memory only
        $this->state = ['tasks' => [], 'lastId' => 0];
    }

    /**
     * @return array{tasks: array<int, array{id:int,title:string,done:bool}>, lastId:int}
     */
    private function read(): array
    {
        return $this->state;
    }

    private function write(array $data): void
    {
        $this->state = $data;
    }

    /**
     * @return array<int, array{id:int,title:string,done:bool}>
     */
    public function list(): array
    {
        $data = $this->read();
        return array_values($data['tasks']);
    }

    /**
     * @param array<int, string> $titles
     * @return array<int, array{id:int,title:string,done:bool}>
     */
    public function addMany(array $titles): array
    {
        $data = $this->read();
        $created = [];
        foreach ($titles as $title) {
            Log::info("Create task: $title");
            $data['lastId'] = (int)$data['lastId'] + 1;
            $task = [
                'id' => $data['lastId'],
                'title' => (string)$title,
                'done' => false,
            ];
            $data['tasks'][] = $task;
            $created[] = $task;
        }
        $this->write($data);
        return $created;
    }

    public function complete(int $id): ?array
    {
        Log::info("Complete task $id");

        $data = $this->read();
        foreach ($data['tasks'] as &$task) {
            if ((int)$task['id'] === $id) {
                $task['done'] = true;
                $this->write($data);
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
        $data = $this->read();
        $total = count($data['tasks']);
        $completed = 0;
        
        foreach ($data['tasks'] as $task) {
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
}


