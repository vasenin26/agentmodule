<?php

namespace Anymodule\Agentmodule\Services\ToolsService;

use Anymodule\Agentmodule\Interface\LLMTools;
use Anymodule\Agentmodule\Interface\ToolInterface;
use Anymodule\Agentmodule\Services\ToolsService\Tools\SendResult;

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
}
