<?php

namespace Anymodule\Agentmodule\Services\ChatGPTMapper\Container;

use Anymodule\Agentmodule\Utils\Log;

class TaskList
{
    const INSTRUCTION = <<<INT
Below is my current task list. This is my working plan. Each task has a status indicator:
- `[todo]` — not started yet\n- `[current]` — currently active task\n- `[done]` — task completed
I must continue working according to this plan. Focus on the `[current]` task. 
When it's completed, i'll it as `[done]`, then move to the next `[todo]`.
Current task list: \n\n
INT;


    public function __construct(
        private array $tasks
    )
    {
    }

    public function getMessage(): array
    {
        $list = [];
        $hasCurrent = false;

        foreach ($this->tasks as $task) {
            $status = 'todo';

            $title = $task['title'] ?? null;
            $done = $task['done'] ?? false;

            if (empty($title)) {
                Log::info("Task list contain empty title", $task);
            }

            if ($done) {
                $status = 'done';
            }

            if($status === 'todo' && !$hasCurrent) {
                $hasCurrent = true;
                $status = 'current';
            }

            $list[] = "- [$status] $title";
        }

        return [
            "role" => "assistant",
            "content" => self::INSTRUCTION . join("\n", $list),
        ];
    }
}