<?php

namespace Anymodule\Agentmodule\Interface\Tools;

interface ToolInterface
{
    public function execute(array $args): ?string;
    public function getProps($name): array;
}
