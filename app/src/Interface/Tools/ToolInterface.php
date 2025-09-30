<?php

namespace Anymodule\Agentmodule\Interface\Tools;

use Anymodule\Agentmodule\Entity\ToolResult;

interface ToolInterface
{
    public function execute(array $args): ?ToolResult;
    public function getProps(): array;
    public function getName(): string;
}
