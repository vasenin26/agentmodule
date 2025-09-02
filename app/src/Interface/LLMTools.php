<?php

namespace Anymodule\Agentmodule\Interface;

interface LLMTools
{
    public function isResultFunction(string $name): bool;

    public function getMeta(): array;

    public function callTool(string $toolName, string $args): ?string;
}
