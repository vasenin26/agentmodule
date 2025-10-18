<?php

namespace Anymodule\Agentmodule\Application\Tools\Tasks;

use Anymodule\Agentmodule\Utils\Log;

class TasksStorage
{
    /**
     * Per-instance in-memory state.
     * @var array{tasks: array<int, array{id:int,title:string,done:bool}>, lastId:int}
     */
    private array $state;

    /**
     * Path to the persistent storage file
     */
    private string $storagePath;

    public function __construct(?string $storagePath = null)
    {
        // Use context directory for persistent storage
        $this->storagePath = $storagePath ?? '/home/local/context/tasks_storage.json';
        
        // Load existing state or initialize with defaults
        $this->state = $this->load();
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
        $this->save();
    }

    /**
     * Load state from persistent storage
     * @return array{tasks: array<int, array{id:int,title:string,done:bool}>, lastId:int}
     */
    private function load(): array
    {
        if (!file_exists($this->storagePath)) {
            Log::info("TasksStorage: No existing storage found, initializing with defaults");
            return ['tasks' => [], 'lastId' => 0];
        }

        try {
            $content = file_get_contents($this->storagePath);
            if ($content === false) {
                Log::warning("TasksStorage: Failed to read storage file");
                return ['tasks' => [], 'lastId' => 0];
            }

            $data = json_decode($content, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                Log::warning("TasksStorage: Invalid JSON in storage file: " . json_last_error_msg());
                return ['tasks' => [], 'lastId' => 0];
            }

            // Validate data structure
            if (!isset($data['tasks']) || !isset($data['lastId'])) {
                Log::warning("TasksStorage: Invalid data structure in storage file");
                return ['tasks' => [], 'lastId' => 0];
            }

            Log::info("TasksStorage: Successfully loaded state from persistent storage");
            return $data;
        } catch (\Exception $e) {
            Log::warning("TasksStorage: Error loading state: " . $e->getMessage());
            return ['tasks' => [], 'lastId' => 0];
        }
    }

    /**
     * Save state to persistent storage
     */
    private function save(): void
    {
        try {
            // Ensure directory exists
            $dir = dirname($this->storagePath);
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }

            $json = json_encode($this->state, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            if ($json === false) {
                Log::warning("TasksStorage: Failed to encode state to JSON");
                return;
            }

            $result = file_put_contents($this->storagePath, $json, LOCK_EX);
            if ($result === false) {
                Log::warning("TasksStorage: Failed to write state to storage file");
            } else {
                Log::info("TasksStorage: State saved to persistent storage");
            }
        } catch (\Exception $e) {
            Log::warning("TasksStorage: Error saving state: " . $e->getMessage());
        }
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

    /**
     * Clear all tasks and reset storage
     */
    public function clear(): void
    {
        Log::info("TasksStorage: Clearing all tasks");
        $this->state = ['tasks' => [], 'lastId' => 0];
        $this->save();
    }

    /**
     * Get storage file path
     */
    public function getStoragePath(): string
    {
        return $this->storagePath;
    }
}


