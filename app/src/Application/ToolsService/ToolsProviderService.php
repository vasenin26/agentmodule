<?php

namespace Anymodule\Agentmodule\Application\ToolsService;

use Anymodule\Agentmodule\Entity\ToolResult;
use Anymodule\Agentmodule\Interface\Tools\ToolInterface;
use Anymodule\Agentmodule\Interface\Tools\ToolsProviderInterface;

class ToolsProviderService implements ToolsProviderInterface
{
    private array $meta = [];
    private array $map = [];

    /**
     * @param array<string, ToolInterface> $tools
     */
    public function __construct(
        array $tools
    )
    {
        foreach ($tools as $key => $tool) {
            $this->register($key, $tool);
        }
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

        if (json_last_error() !== JSON_ERROR_NONE) {
            $params = [];
        }

        if(!array_key_exists($toolName, $this->map)) {
            return new ToolResult(false, "Tool {$toolName} not found");
        }

        try {
            return $this->map[$toolName]->execute($params);
        } catch (\Throwable $exception) {
            return new ToolResult(false, $exception->getMessage(), ['exception' => get_class($exception)]);
        }
    }

    public function getTaskTool(): ?ToolInterface
    {
        return $this->map['tasks-list'] ?? null;
    }
}
