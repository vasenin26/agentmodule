<?php

namespace Anymodule\Agentmodule\Services\ToolsService;

use Anymodule\Agentmodule\Interface\Tools\ToolsProvider;
use Anymodule\Agentmodule\Interface\Tools\ToolInterface;
use Anymodule\Agentmodule\Tools\SendResult;
use Anymodule\Agentmodule\Entity\ToolResult;

class ToolsProviderService implements ToolsProvider
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
        $this->meta[$name] = $tool->getProps();
        $this->map[$name] = $tool;
    }

    public function getMeta(): array
    {
        return array_values($this->meta);
    }

    public function callTool(string $toolName, string $args): ?ToolResult
    {
        $params = json_decode($args, true);

        if(json_last_error() !== JSON_ERROR_NONE){
            $params = [];
        }

        try {
            return $this->map[$toolName]->execute($params);
        } catch (\Throwable $exception) {
            return new ToolResult(false, $exception->getMessage(), ['exception' => get_class($exception)]);
        }
    }

    public function isResultFunction(string $name): bool
    {
        return $name === SendResult::NAME;
    }

    public function getTaskTool(): ?ToolInterface
    {
        return $this->map['tasks-list'] ?? null;
    }

    public function getTodo(): int
    {
        $taskTool = $this->getTaskTool();

        if($taskTool) {
            /** @var ToolResult|null $tasksResult */
            $tasksResult = $taskTool->execute([]);

            if($tasksResult && $tasksResultJson = (string)$tasksResult) {
                $decoded = json_decode($tasksResultJson, true);
                $items = $decoded['payload'] ?? [];
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
