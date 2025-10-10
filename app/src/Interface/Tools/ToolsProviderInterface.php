<?php

namespace Anymodule\Agentmodule\Interface\Tools;

use Anymodule\Agentmodule\Entity\ToolResult;

interface ToolsProviderInterface
{
    public function getMeta(): array;

    public function callTool(string $toolName, string $args): ?ToolResult;

    public function getTaskTool(): ?ToolInterface;
}
