<?php

namespace Anymodule\Agentmodule\Services\ToolsService;

use Anymodule\Agentmodule\Interface\Tools\LLMTools;
use Anymodule\Agentmodule\Interface\Tools\ToolInterface;
use Anymodule\Agentmodule\Tools\SendResult;

class ToolsService implements LLMTools
{
    const RESULT_TOOL = 'result';

    private array $meta = [];
    private array $map = [];

    /**
     * @param SendResult $sendResult
     * @param array<string, ToolInterface> $tools
     */
    public function __construct(
        ToolInterface $sendResult,
        array      $tools
    )
    {
        foreach ($tools as $key => $tool) {
            $this->register($key, $tool);
        }

        $this->register(self::RESULT_TOOL, $sendResult);
    }

    public function register(string $name, ToolInterface $tool): void
    {
        $this->meta[$name] = $tool->getProps($name);
        $this->map[$name] = $tool;
    }

    public function getMeta(): array
    {
        return array_values($this->meta);
    }

    public function callTool(string $toolName, string $args): ?string
    {
        $params = json_decode($args, true);

        if(json_last_error() !== JSON_ERROR_NONE){
            $params = [];
        }

        return $this->map[$toolName]->execute($params);
    }

    public function isResultFunction(string $name): bool
    {
        return $name === self::RESULT_TOOL;
    }

    public function getTaskTool(): ?ToolInterface
    {
        return $this->map['tasks-list'] ?? null;
    }

    public function getTodo(): int
    {
        $taskTool = $this->getTaskTool();

        if($taskTool) {
            $tasks = $taskTool->execute([]);

            if($tasks) {
                $items = json_decode($tasks, true);
                $await = 0;

                foreach ($items as $item) {
                    if($item['done'] ?? false) continue;
                    $await++;
                }

                return $await;
            }
        }

        return 0;
    }
}
