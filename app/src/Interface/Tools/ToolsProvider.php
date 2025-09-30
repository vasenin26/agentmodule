<?php

namespace Anymodule\Agentmodule\Interface\Tools;

use Anymodule\Agentmodule\Entity\ToolResult;

interface ToolsProvider
{
    public function isResultFunction(string $name): bool;

    public function getMeta(): array;

    public function callTool(string $toolName, string $args): ?ToolResult;

    public function getTaskTool(): ?ToolInterface;
}
